import { Estudiante } from './GestionEstudiantes';
import { Seccion, HorarioSeccion } from './InfraestructuraHorarios';

export enum EstadoCola {
  EN_ESPERA = 'EN_ESPERA',
  EN_TURNO = 'EN_TURNO',
  FINALIZADO = 'FINALIZADO',
  ABANDONADO = 'ABANDONADO'
}

export enum TipoMatricula {
  REGULAR = 'REGULAR',
  EXTEMPORANEA = 'EXTEMPORANEA',
  ESPECIAL = 'ESPECIAL'
}

export enum EstadoMatricula {
  PENDIENTE = 'PENDIENTE',
  CONFIRMADA = 'CONFIRMADA',
  OBSERVADA = 'OBSERVADA',
  ANULADA = 'ANULADA'
}

export enum EstadoDetalleMatricula {
  INSCRITO = 'INSCRITO',
  RETIRADO = 'RETIRADO',
  APROBADO = 'APROBADO',
  DESAPROBADO = 'DESAPROBADO'
}

export enum OrigenMatricula {
  WEB = 'WEB',
  ADMINISTRATIVO = 'ADMINISTRATIVO',
  RECTIFICACION = 'RECTIFICACION'
}

export enum TipoExcepcion {
  BYPASS_CRUCE = 'BYPASS_CRUCE',
  BYPASS_DEUDA = 'BYPASS_DEUDA',
  BYPASS_MEDICO = 'BYPASS_MEDICO',
  AMPLIACION_CREDITOS = 'AMPLIACION_CREDITOS'
}

export enum EstadoRectificacion {
  SOLICITADO = 'SOLICITADO',
  APROBADO = 'APROBADO',
  RECHAZADO = 'RECHAZADO'
}

export enum TipoMovimiento {
  ALTA = 'ALTA',
  BAJA = 'BAJA',
  CAMBIO_SECCION = 'CAMBIO_SECCION'
}

export class ColaMatricula {
  constructor(
    public idCola: number,
    public idEstudiante: number,
    public idPeriodo: number,
    public posicion: number,
    public estadoCola: EstadoCola = EstadoCola.EN_ESPERA,
    public priorizado: boolean = false,
    public horaIngreso: Date = new Date()
  ) {}

  public atenderSiguiente(): void {
    this.estadoCola = EstadoCola.EN_TURNO;
  }

  public abandonarCola(): void {
    this.estadoCola = EstadoCola.ABANDONADO;
  }

  public finalizarTurno(): void {
    this.estadoCola = EstadoCola.FINALIZADO;
  }

  public priorizarAtencion(motivo: string): void {
    this.priorizado = true;
    this.posicion = 1;
  }
}

export class DetalleMatricula {
  constructor(
    public idDetalle: number,
    public idMatricula: number,
    public idSeccion: number,
    public estado: EstadoDetalleMatricula = EstadoDetalleMatricula.INSCRITO,
    public notaFinal: number = 0.0,
    public notaTeoria: number = 0.0,
    public notaPractica: number = 0.0,
    public origen: OrigenMatricula = OrigenMatricula.WEB,
    public seccion?: Seccion
  ) {}

  public estaAprobado(): boolean {
    return this.notaFinal >= 10.5;
  }
}

export class ExcepcionMatricula {
  constructor(
    public idExcepcion: number,
    public idMatricula: number,
    public tipoExcepcion: TipoExcepcion,
    public numeroRD: string,
    public descripcion: string,
    public idAdmin: number,
    public fecha: Date = new Date()
  ) {}
}

export class Matricula {
  constructor(
    public idMatricula: number,
    public idEstudiante: number,
    public idPeriodo: number,
    public tipoMatricula: TipoMatricula = TipoMatricula.REGULAR,
    public estado: EstadoMatricula = EstadoMatricula.PENDIENTE,
    public totalCreditos: number = 0,
    public creditosAdicionales: number = 0,
    public bypassCruces: boolean = false,
    public bypassDeuda: boolean = false,
    public bypassMedico: boolean = false,
    public nroConstancia: string = '',
    public fechaMatricula: Date = new Date(),
    public detalles: DetalleMatricula[] = []
  ) {}

  public validarLimiteCreditos(creditosNuevos: number): boolean {
    const limiteBase = 22;
    return (this.totalCreditos + creditosNuevos) <= (limiteBase + this.creditosAdicionales);
  }

  public detectarCruceHorario(nuevaSeccion: Seccion): boolean {
    if (this.bypassCruces) return false;
    for (const det of this.detalles) {
      if (det.estado !== EstadoDetalleMatricula.INSCRITO || !det.seccion) continue;
      for (const h1 of det.seccion.horarios) {
        for (const h2 of nuevaSeccion.horarios) {
          if (h1.haySolapamiento(h2)) {
            return true;
          }
        }
      }
    }
    return false;
  }

  public inscribirCurso(seccion: Seccion, creditosCurso: number): DetalleMatricula | null {
    if (!this.validarLimiteCreditos(creditosCurso)) {
      throw new Error('Límite de créditos excedido');
    }
    if (this.detectarCruceHorario(seccion)) {
      throw new Error('Cruce de horario detectado con otra sección');
    }
    if (!seccion.reservarVacante()) {
      throw new Error('No hay vacantes disponibles en la sección');
    }

    const detalle = new DetalleMatricula(
      Date.now(),
      this.idMatricula,
      seccion.idSeccion,
      EstadoDetalleMatricula.INSCRITO,
      0, 0, 0,
      OrigenMatricula.WEB,
      seccion
    );

    this.detalles.push(detalle);
    this.totalCreditos += creditosCurso;
    return detalle;
  }

  public retirarCurso(seccion: Seccion, creditosCurso: number): boolean {
    const idx = this.detalles.findIndex(d => d.idSeccion === seccion.idSeccion && d.estado === EstadoDetalleMatricula.INSCRITO);
    if (idx !== -1) {
      this.detalles[idx].estado = EstadoDetalleMatricula.RETIRADO;
      seccion.liberarVacante();
      this.totalCreditos -= creditosCurso;
      return true;
    }
    return false;
  }

  public confirmarMatricula(): string {
    if (this.detalles.length === 0) {
      throw new Error('No cuenta con cursos seleccionados');
    }
    this.estado = EstadoMatricula.CONFIRMADA;
    this.nroConstancia = `CONST-${new Date().getFullYear()}-${String(this.idMatricula).padStart(6, '0')}`;
    return this.nroConstancia;
  }
}

export class DetalleRectificacion {
  constructor(
    public idDetalleRect: number,
    public idRectificacion: number,
    public idSeccion: number,
    public tipoMovimiento: TipoMovimiento,
    public creditos: number,
    public motivo: string
  ) {}
}

export class Rectificacion {
  constructor(
    public idRectificacion: number,
    public idMatricula: number,
    public idAdmin: number,
    public nroActa: string = '',
    public estado: EstadoRectificacion = EstadoRectificacion.SOLICITADO,
    public observaciones: string = '',
    public fechaInicio: Date = new Date(),
    public fechaConfirmacion: Date | null = null,
    public movimientos: DetalleRectificacion[] = []
  ) {}

  public agregarMovimiento(seccion: Seccion, tipo: TipoMovimiento, creditos: number, motivo: string): void {
    const item = new DetalleRectificacion(
      Date.now(),
      this.idRectificacion,
      seccion.idSeccion,
      tipo,
      creditos,
      motivo
    );
    this.movimientos.push(item);
  }

  public simularCarga(): boolean {
    return true;
  }

  public aplicarRectificacion(): string {
    this.estado = EstadoRectificacion.APROBADO;
    this.fechaConfirmacion = new Date();
    this.nroActa = `REC-${new Date().getFullYear()}-${String(this.idRectificacion).padStart(5, '0')}`;
    return this.nroActa;
  }
}

