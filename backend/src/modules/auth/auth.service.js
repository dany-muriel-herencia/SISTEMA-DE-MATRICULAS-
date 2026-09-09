import { UnauthorizedError } from '../../shared/errors/unauthorized-error.js';

export class AuthService {
  constructor(authRepository) {
    this.authRepository = authRepository;
  }

  async login(email, password) {
    const user = await this.authRepository.findUserByEmail(email);
    if (!user) {
      throw new UnauthorizedError('Credenciales incorrectas');
    }
    // En etapa posterior se usará bcrypt para comparar password y generar JWT
    return {
      user: {
        id: user.id,
        email: user.email,
        nombre: user.nombre,
        apellido: user.apellido,
        rol: user.rol_nombre
      },
      token: 'jwt_placeholder_token'
    };
  }
}
