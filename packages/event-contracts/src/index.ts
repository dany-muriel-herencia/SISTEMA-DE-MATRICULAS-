export interface DomainEvent<T = unknown> {
  eventId: string;
  eventType: string;
  timestamp: string;
  aggregateId: string;
  payload: T;
}

export interface MatriculaConfirmadaPayload {
  idMatricula: number;
  idEstudiante: number;
  codigoEstudiante: string;
  idPeriodo: number;
  totalCreditos: number;
  nroConstancia: string;
  seccionesInscritas: number[];
}

export interface VacanteModificadaPayload {
  idSeccion: number;
  idCurso: number;
  vacantesDisponibles: number;
  vacantesOcupadas: number;
}

export interface PagoRegistradoPayload {
  idPago: number;
  idDeuda: number;
  idEstudiante: number;
  monto: number;
  nroComprobante: string;
}

export interface RectificacionProcesadaPayload {
  idRectificacion: number;
  idMatricula: number;
  nroActa: string;
  altas: number[];
  bajas: number[];
}

