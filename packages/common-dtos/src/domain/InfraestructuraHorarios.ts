export enum TipoAula {
  TEORIA = 'TEORIA',
  LABORATORIO = 'LABORATORIO',
  AUDITORIO = 'AUDITORIO'
}

export enum TurnoEnum {
  MANANA = 'MANANA',
  TARDE = 'TARDE',
  NOCHE = 'NOCHE'
}

export enum DiaSemana {
  LUNES = 'LUNES',
  MARTES = 'MARTES',
  MIERCOLES = 'MIERCOLES',
  JUEVES = 'JUEVES',
  VIERNES = 'VIERNES',
  SABADO = 'SABADO'
}

export class Aula {
  constructor(
    public idAula: number,
    public codigoAula: string,
    public pabellon: string,
    public piso: number,
    public capacidadAforo: number,
    public tipo: TipoAula = TipoAula.TEORIA
  ) {}

  public verificarAforo(vacantes: number): boolean {
    return vacantes <= this.capacidadAforo;
  }
}

export class HorarioSeccion {
  constructor(
    public idHorario: number,
    public idSeccion: number,
    public diaSemana: DiaSemana,
    public horaInicio: string, // "HH:mm"
    public horaFin: string    // "HH:mm"
  ) {}

  public haySolapamiento(otro: HorarioSeccion): boolean {
    if (this.diaSemana !== otro.diaSemana) return false;
    return this.horaInicio < otro.horaFin && this.horaFin > otro.horaInicio;
  }
}

export class Seccion {
  constructor(
    public idSeccion: number,
    public idPeriodo: number,
    public idCurso: number,
    public idAula: number,
    public idDocente: number,
    public codigoSeccion: string,
    public turno: TurnoEnum = TurnoEnum.MANANA,
    public vacantesTotal: number = 40,
    public vacantesOcupadas: number = 0,
    public vacantesExtra: number = 0,
    public horarios: HorarioSeccion[] = []
  ) {}

  public obtenerVacantesDisponibles(): number {
    return (this.vacantesTotal + this.vacantesExtra) - this.vacantesOcupadas;
  }

  public reservarVacante(): boolean {
    if (this.obtenerVacantesDisponibles() > 0) {
      this.vacantesOcupadas++;
      return true;
    }
    return false;
  }

  public liberarVacante(): boolean {
    if (this.vacantesOcupadas > 0) {
      this.vacantesOcupadas--;
      return true;
    }
    return false;
  }

  public ampliarVacantes(extra: number, nroRes: string): boolean {
    if (extra > 0 && nroRes.trim().length > 0) {
      this.vacantesExtra += extra;
      return true;
    }
    return false;
  }
}

