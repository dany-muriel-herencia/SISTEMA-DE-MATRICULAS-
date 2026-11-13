export enum EstadoAcademico {
  REGULAR = 'REGULAR',
  OBSERVADO = 'OBSERVADO',
  INVICTO = 'INVICTO',
  EGRESADO = 'EGRESADO',
  GRADUADO = 'GRADUADO',
  SUSPENDIDO = 'SUSPENDIDO'
}

export enum TipoResolucion {
  AMPLIACION_CREDITOS = 'AMPLIACION_CREDITOS',
  CONVALIDACION = 'CONVALIDACION',
  TRASLADO_INTERNO = 'TRASLADO_INTERNO',
  TRASLADO_EXTERNO = 'TRASLADO_EXTERNO',
  SUSPENSION = 'SUSPENSION'
}

export class FichaMedica {
  constructor(
    public idFicha: number,
    public idEstudiante: number,
    public aptoParaMatricula: boolean = false,
    public fechaEvaluacion: Date | null = null,
    public observaciones: string = ''
  ) {}

  public validarAptitud(): boolean {
    return this.aptoParaMatricula;
  }
}

export class ResolucionEstudiante {
  constructor(
    public idResolucion: number,
    public idEstudiante: number,
    public nroResolucion: string,
    public tipoResolucion: TipoResolucion,
    public creditosOtorgados: number,
    public fechaEmision: Date,
    public archivoSustentoUrl: string = ''
  ) {}
}

export class Estudiante {
  constructor(
    public idEstudiante: number,
    public idUsuario: number,
    public idEscuela: number,
    public idPlan: number,
    public codigoEstudiante: string,
    public estadoAcademico: EstadoAcademico = EstadoAcademico.REGULAR,
    public promedioSemestreAnterior: number = 0.0,
    public promedioPonderadoAcumulado: number = 0.0,
    public creditosAprobados: number = 0,
    public anioIngreso: number = 2026,
    public modalidadIngreso: string = 'EXAMEN_ORDINARIO'
  ) {}

  public generarCodigoInstitucional(): string {
    const correlativo = String(this.idEstudiante).padStart(6, '0');
    this.codigoEstudiante = `${this.anioIngreso}-${correlativo}`;
    return this.codigoEstudiante;
  }

  public consultarKardex(): {
    codigoEstudiante: string;
    creditosAprobados: number;
    promedioPonderado: number;
    condicion: string;
  } {
    return {
      codigoEstudiante: this.codigoEstudiante,
      creditosAprobados: this.creditosAprobados,
      promedioPonderado: this.promedioPonderadoAcumulado,
      condicion: this.estadoAcademico
    };
  }

  public verificarRequisitosGraduacion(totalCreditosRequeridos: number = 200): boolean {
    return this.creditosAprobados >= totalCreditosRequeridos;
  }

  public actualizarCondicion(estado: EstadoAcademico): void {
    this.estadoAcademico = estado;
  }
}

