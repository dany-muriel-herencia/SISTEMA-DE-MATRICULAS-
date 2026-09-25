import assert from 'node:assert/strict';
const base = process.env.SGAU_TEST_URL;
if (!base || !/^http:\/\/127\.0\.0\.1:\d+$/.test(base)) throw new Error('Define SGAU_TEST_URL con el servidor ui_router.php local.');
let token = '';
async function request(path, method = 'GET', body) {
  const response = await fetch(`${base}/backend/public/api/${path}`, { method, headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}) }, ...(body ? { body: JSON.stringify(body) } : {}) });
  return { status: response.status, ...(await response.json()) };
}
assert.equal((await request('auth/me')).status, 401);
assert.equal((await request('secciones/catalogos')).status, 401);
const login = await request('auth/login','POST',{email:'docente@example.test',password:'Prueba-local-2026'});
assert.equal(login.status,200); token=login.data.token;
const profile=await request('auth/me');
assert.equal(profile.data.rol,'DOCENTE'); assert.equal('contrasenha' in profile.data,false);
const catalog=await request('secciones/catalogos');
assert.equal(catalog.status,200); assert.equal(catalog.data.docentes[0].nombre,'Docente de prueba');
assert.equal('email' in catalog.data.docentes[0],false);
assert.equal((await request('secciones?periodo_id=1')).status,200);
assert.equal((await request('secciones','POST',{})).status,403);
assert.equal((await request('secciones/1/horarios','POST',{})).status,403);
assert.equal((await request('auth/logout','POST')).status,200);
assert.equal((await request('auth/me')).status,401);
console.log('PASS: acceso, perfil sin contraseña, catálogos, permisos de escritura y revocación por HTTP.');
