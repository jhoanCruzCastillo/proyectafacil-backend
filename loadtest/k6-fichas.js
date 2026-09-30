// Prueba de carga de proyectafacil-backend con k6 (https://k6.io).
//
// Simula el uso real de un cliente editando una ficha: iniciar sesión, abrir la ficha y guardar
// campos repetidamente. El guardado (PUT /api/ejemplos/:id) reemplaza el mapa `valores` COMPLETO,
// no aplica un parche (ver EjemplosController::update) — el frontend real siempre manda el mapa ya
// fusionado (useClienteFichaEditor.ts::handleSave), así que este script hace lo mismo: GET antes de
// cada PUT, fusiona un campo de prueba encima de lo que ya había, y recién ahí guarda. Sin este
// paso, cada guardado BORRARÍA todos los demás campos de la ficha.
//
// ⚠️ NUNCA apuntar LOAD_TEST_EJEMPLO_ID a una ficha real de un cliente — el campo de prueba que
// este script agrega (`load-test-campo-<VU>`) queda escrito ahí. Usa una ficha descartable creada
// a propósito para esto (o bórrala/recréala después de la prueba).
//
// ⚠️ Correr esto contra producción afecta al servicio real mientras dure la prueba (mismo backend
// que atiende a los clientes) — coordina un horario de bajo tráfico y avisa antes de correrlo.
//
// Instalar k6: https://grafana.com/docs/k6/latest/set-up/install-k6/
//   Windows:  choco install k6      (o winget install k6.k6)
//
// Uso — primero un smoke test con pocos VUs para confirmar que todo funciona:
//   k6 run --env BASE_URL=https://proyectafacil-backend-production.up.railway.app ^
//          --env LOAD_TEST_USUARIO=<usuario_de_prueba> ^
//          --env LOAD_TEST_PASSWORD=<password> ^
//          --env LOAD_TEST_EJEMPLO_ID=<id_de_una_ficha_descartable> ^
//          --env SMOKE=1 loadtest/k6-fichas.js
//
// Luego la prueba completa (sin SMOKE=1), que rampea hasta 150 usuarios simultáneos guardando
// campos — ajusta los `stages` de abajo según lo que te pidan probar:
//   k6 run --env BASE_URL=... --env LOAD_TEST_USUARIO=... --env LOAD_TEST_PASSWORD=... ^
//          --env LOAD_TEST_EJEMPLO_ID=... loadtest/k6-fichas.js
//
// Reporte: k6 imprime un resumen al terminar (p50/p95/p99 de latencia, % de errores). Para un
// dashboard en vivo, agrega `--out json=resultado.json` o usa k6 Cloud.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const USUARIO = __ENV.LOAD_TEST_USUARIO;
const PASSWORD = __ENV.LOAD_TEST_PASSWORD;
const EJEMPLO_ID = __ENV.LOAD_TEST_EJEMPLO_ID;
const SMOKE = __ENV.SMOKE === '1';

if (!USUARIO || !PASSWORD || !EJEMPLO_ID) {
  throw new Error(
    'Faltan variables de entorno: LOAD_TEST_USUARIO, LOAD_TEST_PASSWORD y LOAD_TEST_EJEMPLO_ID son obligatorias. '
    + 'Nunca hardcodear credenciales ni ids reales en este archivo.',
  );
}

const latenciaLogin = new Trend('latencia_login');
const latenciaAbrirFicha = new Trend('latencia_abrir_ficha');
const latenciaGuardarCampo = new Trend('latencia_guardar_campo');
const erroresGuardar = new Rate('errores_guardar_campo');

// `SMOKE=1`: una corrida corta con pocos VUs, para confirmar que el script y las credenciales
// funcionan antes de lanzar la prueba de carga real (que además consume cuota de OpenAI si el
// backend dispara IA en el camino — este flujo no lo hace, pero es buen hábito probar chico primero).
const stagesEdicion = SMOKE
  ? [{ duration: '20s', target: 3 }, { duration: '20s', target: 0 }]
  : [
      { duration: '1m', target: 25 },   // calentamiento
      { duration: '2m', target: 75 },   // carga media
      { duration: '2m', target: 150 },  // carga alta — el número que probablemente te pidieron
      { duration: '1m', target: 0 },    // enfriamiento
    ];

const stagesLogin = SMOKE
  ? [{ duration: '20s', target: 2 }, { duration: '20s', target: 0 }]
  : [
      { duration: '30s', target: 10 },
      { duration: '1m', target: 10 },
      { duration: '30s', target: 0 },
    ];

export const options = {
  scenarios: {
    // Login real (password_verify/bcrypt) bajo carga — mide ese costo por separado del uso normal,
    // porque es intrínsecamente más caro por request que leer/guardar una ficha.
    logins: {
      executor: 'ramping-vus',
      exec: 'flujoLogin',
      startVUs: 0,
      stages: stagesLogin,
    },
    // El uso real: abrir una ficha y guardar campos repetidamente, reusando UN token obtenido en
    // setup() — así el costo de autenticación no se mezcla con el costo de uso normal.
    edicion_fichas: {
      executor: 'ramping-vus',
      exec: 'flujoEdicionFicha',
      startVUs: 0,
      stages: stagesEdicion,
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.02'],
    http_req_duration: ['p(95)<800'],
    latencia_guardar_campo: ['p(95)<1000'],
  },
};

function login() {
  const res = http.post(
    `${BASE_URL}/api/auth/login`,
    JSON.stringify({ usuario: USUARIO, password: PASSWORD }),
    { headers: { 'Content-Type': 'application/json' } },
  );
  check(res, { 'login: 200': (r) => r.status === 200 });
  return res;
}

export function setup() {
  const res = login();
  if (res.status !== 200) {
    throw new Error(`No se pudo iniciar sesión con las credenciales de prueba (status ${res.status}): ${res.body}`);
  }
  return { token: res.json('token') };
}

export function flujoLogin() {
  const res = login();
  latenciaLogin.add(res.timings.duration);
  sleep(1);
}

export function flujoEdicionFicha(data) {
  const headers = { Authorization: `Bearer ${data.token}`, 'Content-Type': 'application/json' };

  // 1) Abrir la ficha — como cuando un usuario entra a /mis-fichas/:id.
  const get = http.get(`${BASE_URL}/api/ejemplos/${EJEMPLO_ID}`, { headers });
  latenciaAbrirFicha.add(get.timings.duration);
  const getOk = check(get, { 'get ejemplo: 200': (r) => r.status === 200 });
  if (!getOk) {
    erroresGuardar.add(true);
    sleep(1);
    return;
  }

  const body = get.json();
  sleep(Math.random() * 2 + 1); // tiempo de "pensar" antes de escribir, como un usuario real

  // 2) Guardar — fusiona un campo de prueba sobre lo que ya había, igual que hace el frontend real,
  // para no pisar el resto de los campos de la ficha (ver nota al inicio del archivo).
  const valoresFusionados = {
    ...body.valores,
    [`load-test-campo-${__VU}`]: `valor de prueba VU ${__VU} iter ${__ITER} ${Date.now()}`,
  };
  const put = http.put(
    `${BASE_URL}/api/ejemplos/${EJEMPLO_ID}`,
    JSON.stringify({ valores: valoresFusionados, fuentes: body.fuentes, origen: body.origen }),
    { headers },
  );
  latenciaGuardarCampo.add(put.timings.duration);
  const putOk = check(put, { 'put ejemplo: 200': (r) => r.status === 200 });
  erroresGuardar.add(!putOk);

  sleep(Math.random() * 3 + 1);
}
