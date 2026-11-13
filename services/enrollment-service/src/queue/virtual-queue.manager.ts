export interface QueueTicket {
  idEstudiante: number;
  idPeriodo: number;
  posicion: number;
  priorizado: boolean;
  horaIngreso: Date;
}

export class VirtualQueueManager {
  private queue: QueueTicket[] = [];

  public ingresarCola(idEstudiante: number, idPeriodo: number, priorizado: boolean = false): QueueTicket {
    const ticket: QueueTicket = {
      idEstudiante,
      idPeriodo,
      posicion: priorizado ? 1 : this.queue.length + 1,
      priorizado,
      horaIngreso: new Date()
    };

    if (priorizado) {
      this.queue.unshift(ticket);
      this.recalcularPosiciones();
    } else {
      this.queue.push(ticket);
    }

    return ticket;
  }

  public obtenerSiguiente(): QueueTicket | null {
    return this.queue.shift() || null;
  }

  private recalcularPosiciones(): void {
    this.queue.forEach((item, index) => {
      item.posicion = index + 1;
    });
  }
}

