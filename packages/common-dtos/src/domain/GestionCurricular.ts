export enum TipoCurso {
  OBLIGATORIO = 'OBLIGATORIO',
  ELECTIVO = 'ELECTIVO'
}

export enum EstadoPlan {
  VIGENTE = 'VIGENTE',
  CERRADO = 'CERRADO',
  EN_DISENIO = 'EN_DISENIO'
}

export enum SemestreEnum {
  I = 'I',
  II = 'II',
  VERANO = 'VERANO'
}

export enum EstadoPeriodo {
  PLANIFICACION = 'PLANIFICACION',
  APERTURADO = 'APERTURADO',
  EN_MATRICULA = 'EN_MATRICULA',
  EN_CURSO = 'EN_CURSO',
  CERRADO = 'CERRADO'
}

export class Curso {
  constructor(
    public idCurso: number,
    public codigoCurso: string,
    public nombre: string,
    public creditos: number,
    public horasTeoria: number,
    public horasPractica: number,
    public tipo: TipoCurso = TipoCurso.OBLIGATORIO
  ) {}

  public esElectivo(): boolean {
    return this.tipo === TipoCurso.ELECTIVO;
  }

  public obtenerTotalHoras(): number {
    return this.horasTeoria + this.horasPractica;
  }
}

export class MallaCurricular {
  constructor(
    public idMalla: number,
    public idPlan: number,
    public idCurso: number,
    public ciclo: number,
    public esObligatorio: boolean = true
  ) {}
}

export class PlanEstudio {
  constructor(
    public idPlan: number,
    public idEscuela: number,
    public codigoPlan: string,
    public anioVigencia: number,
    public totalCreditosRequeridos: number,
    public estado: EstadoPlan = EstadoPlan.VIGENTE,
    public malla: MallaCurricular[] = []
  ) {}

  public validarCreditosMinimos(): boolean {
    return this.totalCreditosRequeridos >= 200;
  }

  public listarMalla(): MallaCurricular[] {
    return this.malla;
  }
}

export class EscuelaProfesional {
  constructor(
    public idEscuela: number,
    public idFacultad: number,
    public codigo: string,
    public nombre: string,
    public director: string,
    public planVigente: PlanEstudio | null = null
  ) {}

  public obtenerPlanVigente(): PlanEstudio | null {
    return this.planVigente;
  }
}

export class Facultad {
  constructor(
    public idFacultad: number,
    public codigo: string,
    public nombre: string,
    public decano: string,
    public escuelas: EscuelaProfesional[] = []
  ) {}

  public listarEscuelas(): EscuelaProfesional[] {
    return this.escuelas;
  }
}

export class Prerrequisito {
  constructor(
    public idCurso: number,
    public idCursoReq: number
  ) {}

  public validarCicloNoCircular(): boolean {
    return this.idCurso !== this.idCursoReq;
  }
}

export class PeriodoAcademico {
  constructor(
    public idPeriodo: number,
    public codigoPeriodo: string,
    public anio: number,
    public semestre: SemestreEnum,
    public inicioMatricula: Date,
    public finMatricula: Date,
    public inicioClases: Date,
    public finClases: Date,
    public estado: EstadoPeriodo = EstadoPeriodo.PLANIFICACION
  ) {}

  public estaEnVentanaMatricula(): boolean {
    const ahora = new Date();
    return (
      this.estado === EstadoPeriodo.EN_MATRICULA &&
      ahora >= this.inicioMatricula &&
      ahora <= this.finMatricula
    );
  }

  public cerrarSemestre(): number {
    this.estado = EstadoPeriodo.CERRADO;
    return this.idPeriodo;
  }
}

