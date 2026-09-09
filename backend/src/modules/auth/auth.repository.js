import { QueryRunner } from '../../database/query-runner.js';

export class AuthRepository {
  async findUserByEmail(email) {
    const [rows] = await QueryRunner.query(
      `SELECT u.id, u.email, u.password_hash, u.nombre, u.apellido, u.rol_id, r.nombre AS rol_nombre, u.activo 
       FROM usuarios u 
       INNER JOIN roles r ON u.rol_id = r.id 
       WHERE u.email = ? LIMIT 1`,
      [email]
    );
    return rows[0] || null;
  }

  async findUserById(id) {
    const [rows] = await QueryRunner.query(
      `SELECT u.id, u.email, u.nombre, u.apellido, u.rol_id, r.nombre AS rol_nombre, u.activo 
       FROM usuarios u 
       INNER JOIN roles r ON u.rol_id = r.id 
       WHERE u.id = ? LIMIT 1`,
      [id]
    );
    return rows[0] || null;
  }
}
