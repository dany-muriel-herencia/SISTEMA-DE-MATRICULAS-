export enum TipoConcepto {
  MATRICULA_REGULAR = 'MATRICULA_REGULAR',
  MATRICULA_EXTEMPORANEA = 'MATRICULA_EXTEMPORANEA',
  RECTIFICACION = 'RECTIFICACION',
  CONSTANCIA = 'CONSTANCIA',
  CERTIFICADO_ESTUDIOS = 'CERTIFICADO_ESTUDIOS'
}

export enum EstadoDeuda {
  PENDIENTE = 'PENDIENTE',
  FRACCIONADO = 'FRACCIONADO',
  PAGADO = 'PAGADO',
  ANULADO = 'ANULADO'
}

export enum MetodoPago {
  VENTANILLA = 'VENTANILLA',
  BANCO_NACION = 'BANCO_NACION',
  PASARELA_ONLINE = 'PASARELA_ONLINE'
}

export enum TipoComprobante {
  BOLETA = 'BOLETA',
  RECIBO_INGRESO = 'RECIBO_INGRESO',
  FACTURA = 'FACTURA'
}

export class ConceptoPago {
  constructor(
    public idConcepto: number,
    public codigoTupa: string,
    public nombre: string,
    public monto: number,
    public tipo: TipoConcepto = TipoConcepto.MATRICULA_REGULAR,
    public vigente: boolean = true
  ) {}
}

export class DeudaEstudiante {
  constructor(
    public idDeuda: number,
    public idEstudiante: number,
    public idConcepto: number,
    public idPeriodo: number,
    public monto: number,
    public saldoPendiente: number,
    public estado: EstadoDeuda = EstadoDeuda.PENDIENTE,
    public fechaVencimiento: Date = new Date()
  ) {}

  public calcularSaldoPendiente(): number {
    return this.saldoPendiente;
  }

  public amortizar(monto: number): void {
    if (monto <= 0) return;
    this.saldoPendiente = Math.max(0, this.saldoPendiente - monto);
    if (this.saldoPendiente === 0) {
      this.estado = EstadoDeuda.PAGADO;
    } else {
      this.estado = EstadoDeuda.FRACCIONADO;
    }
  }
}

export class Comprobante {
  constructor(
    public idComprobante: number,
    public idPago: number,
    public serie: string, // "B001"
    public correlativo: number,
    public tipoComprobante: TipoComprobante = TipoComprobante.BOLETA,
    public montoTotal: number = 0,
    public hashQR: string = '',
    public fechaEmision: Date = new Date()
  ) {}

  public generarQR(): string {
    const raw = `${this.serie}-${String(this.correlativo).padStart(8, '0')}|${this.montoTotal}|${this.fechaEmision.toISOString()}`;
    this.hashQR = Buffer.from(raw).toString('base64');
    return this.hashQR;
  }

  public imprimirDuplicado(): Buffer {
    return Buffer.from(`Comprobante ${this.serie}-${this.correlativo} - Monto: S/ ${this.montoTotal}`);
  }
}

export class Pago {
  constructor(
    public idPago: number,
    public idDeuda: number,
    public idEstudiante: number,
    public idAdmin: number,
    public montoPagado: number,
    public metodoPago: MetodoPago = MetodoPago.VENTANILLA,
    public nroOperacion: string = '',
    public fechaPago: Date = new Date()
  ) {}

  public registrarPagoVentanilla(deuda: DeudaEstudiante, serie: string = 'B001', correlativo: number = 1): Comprobante {
    deuda.amortizar(this.montoPagado);
    const comprobante = new Comprobante(
      Date.now(),
      this.idPago,
      serie,
      correlativo,
      TipoComprobante.BOLETA,
      this.montoPagado
    );
    comprobante.generarQR();
    return comprobante;
  }
}

