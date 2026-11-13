import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';

export interface AuthenticatedUser {
  idUsuario: number;
  idRol: number;
  correo: string;
  rol: string;
}

export function authMiddleware(req: Request, res: Response, next: NextFunction) {
  // Rutas públicas de autenticación y salud
  if (req.path.startsWith('/api/v1/auth/login') || req.path === '/health') {
    return next();
  }

  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Token de autorización no provisto' });
  }

  const token = authHeader.split(' ')[1];
  try {
    const secret = process.env.JWT_SECRET || 'super_secret_jwt_key_unjbg_2026';
    const decoded = jwt.verify(token, secret) as AuthenticatedUser;
    (req as any).user = decoded;
    // Inyectar cabeceras con la identidad para los microservicios downstream
    req.headers['x-user-id'] = String(decoded.idUsuario);
    req.headers['x-user-role'] = String(decoded.rol);
    return next();
  } catch (err) {
    return res.status(401).json({ error: 'Token inválido o expirado' });
  }
}

