export enum EstadoUsuario {
  ACTIVO = 'ACTIVO',
  INACTIVO = 'INACTIVO',
  BLOQUEADO = 'BLOQUEADO'
}

export class Rol {
  constructor(
    public idRol: number,
    public nombre: string,
    public descripcion: string = '',
    public activo: boolean = true,
    public permisos: string[] = []
  ) {}

  public obtenerPermisos(): string[] {
    return this.permisos;
  }
}

export class Usuario {
  constructor(
    public idUsuario: number,
    public idRol: number,
    public dni: string,
    public nombres: string,
    public apellidos: string,
    public correoInstitucional: string,
    public passwordHash: string,
    public estado: EstadoUsuario = EstadoUsuario::ACTIVO ? EstadoUsuario.ACTIVO : EstadoUsuario.ACTIVO,
    public intentosFallidos: number = 0,
    public ultimoLogin: Date | null = null
  ) {}

  public autenticar(passwordValido: boolean): boolean {
    if (this.estado !== EstadoUsuario.ACTIVO) return false;
    if (passwordValido) {
      this.intentosFallidos = 0;
      this.ultimoLogin = new Date();
      return true;
    }
    this.intentosFallidos++;
    if (this.intentosFallidos >= 3) {
      this.bloquearCuenta();
    }
    return false;
  }

  public bloquearCuenta(): void {
    this.estado = EstadoUsuario.BLOQUEADO;
  }

  public restablecerClave(nuevoHash: string): void {
    this.passwordHash = nuevoHash;
    this.intentosFallidos = 0;
    if (this.estado === EstadoUsuario.BLOQUEADO) {
      this.estado = EstadoUsuario.ACTIVO;
    }
  }

  public cambiarRol(nuevoRol: Rol): void {
    this.idRol = nuevoRol.idRol;
  }
}

export class Auditoria {
  constructor(
    public idAuditoria: number,
    public idUsuario: number | null,
    public modulo: string,
    public accion: string,
    public tablaAfectada: string,
    public idRegistro: number,
    public detalle: string,
    public ipCliente: string,
    public fechaHora: Date = new Date()
  ) {}

  public registrarEvento(): void {
    // Inmutable audit log event
  }
}

