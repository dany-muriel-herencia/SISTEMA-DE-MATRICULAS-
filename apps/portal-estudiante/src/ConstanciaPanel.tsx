import React, { useState } from 'react';
import { BadgeCheck, Download, FileCheck2, Fingerprint, LoaderCircle, Printer, QrCode, ShieldCheck, ShieldX } from 'lucide-react';
import QRCode from 'qrcode';
import './ConstanciaPanel.css';

type CursoConstancia = { codigo: string; curso: string; creditos: number; seccion: string; aula: string; notaFinal: string | null };
type Constancia = {
  numero: string; idMatricula: number; estudiante: string; codigoEstudiante: string;
  escuela: string; periodo: string; estado: string; creditos: number;
  fechaEmision: string; cursos: CursoConstancia[];
};
type RespuestaConstancia = { algoritmo: string; hashSha256: string; urlVerificacion: string; constancia: Constancia };

const ConstanciaPanel: React.FC = () => {
  const [documento, setDocumento] = useState<RespuestaConstancia | null>(null);
  const [qr, setQr] = useState('');
  const [cargando, setCargando] = useState(false);
  const [verificando, setVerificando] = useState(false);
  const [verificacion, setVerificacion] = useState<boolean | null>(null);
  const [error, setError] = useState('');

  const emitir = async () => {
    setCargando(true); setError(''); setVerificacion(null);
    try {
      const respuesta = await fetch('/api/constancia.php?codigo=2026-001&periodo=2026-I');
      const datos = await respuesta.json() as RespuestaConstancia & { error?: string };
      if (!respuesta.ok) throw new Error(datos.error || 'No se pudo emitir la constancia.');
      setDocumento(datos);
      setQr(await QRCode.toDataURL(datos.urlVerificacion, { width: 180, margin: 1, errorCorrectionLevel: 'M', color: { dark: '#10243b', light: '#ffffff' } }));
    } catch (fallo) {
      setError(fallo instanceof Error ? fallo.message : 'No se pudo conectar con el servicio de constancias.');
    } finally { setCargando(false); }
  };

  const verificar = async () => {
    if (!documento) return;
    setVerificando(true); setError('');
    try {
      const respuesta = await fetch(documento.urlVerificacion);
      const datos = await respuesta.json() as { verificable?: boolean; mensaje?: string; error?: string };
      setVerificacion(respuesta.ok && datos.verificable === true);
      if (!respuesta.ok && datos.error) setError(datos.error);
    } catch { setVerificacion(false); setError('No se pudo consultar la verificación en la base de datos.'); }
    finally { setVerificando(false); }
  };

  return <section className="constancia-panel">
    <div className="constancia-panel-heading"><div><p className="section-kicker">M05 · Constancia de matrícula</p><h3>Documento oficial verificable</h3><p>Emite la constancia de matrícula confirmada y valida su huella contra la base de datos.</p></div><button type="button" className="constancia-emit-button" onClick={() => void emitir()} disabled={cargando}>{cargando ? <LoaderCircle className="spin" size={16} /> : <FileCheck2 size={16} />}{cargando ? 'Consultando…' : 'Emitir constancia'}</button></div>
    {error && <p className="constancia-error" role="alert">{error}</p>}
    {documento ? <>
      <article className="certificate-print-area">
        <div className="certificate-sheet">
          <header className="certificate-header"><div className="certificate-brand"><div className="certificate-seal"><BadgeCheck size={25} /></div><div><strong>UNIVERSIDAD NACIONAL JORGE BASADRE GROHMANN</strong><span>Sistema de Gestión Académica Universitaria</span></div></div><div className="certificate-official">CONSTANCIA OFICIAL</div></header>
          <div className="certificate-title"><p>CONSTANCIA DE MATRÍCULA</p><span>N.º {documento.constancia.numero}</span></div>
          <p className="certificate-copy">La Oficina de Gestión Académica deja constancia que el/la estudiante:</p>
          <div className="certificate-student"><strong>{documento.constancia.estudiante}</strong><span>Código universitario: {documento.constancia.codigoEstudiante}</span><span>{documento.constancia.escuela}</span></div>
          <p className="certificate-copy">se encuentra matriculado/a en el periodo académico <strong>{documento.constancia.periodo}</strong>, con estado <strong>{documento.constancia.estado}</strong> y un total de <strong>{documento.constancia.creditos} créditos</strong>.</p>
          <div className="certificate-courses"><table><thead><tr><th>Código</th><th>Asignatura</th><th>Sección</th><th>Créditos</th><th>Nota</th></tr></thead><tbody>{documento.constancia.cursos.map((curso) => <tr key={`${curso.codigo}-${curso.seccion}`}><td>{curso.codigo}</td><td>{curso.curso}</td><td>{curso.seccion}</td><td>{curso.creditos}</td><td>{curso.notaFinal ?? '—'}</td></tr>)}</tbody></table></div>
          <div className="certificate-bottom"><div className="certificate-hash"><span><Fingerprint size={15} /> Huella digital · {documento.algoritmo}</span><code>{documento.hashSha256}</code><small>Verifica que el contenido coincida con la matrícula registrada.</small></div>{qr && <div className="certificate-qr"><img src={qr} alt="Código QR para verificar la constancia" /><span><QrCode size={12} /> Escanea para verificar</span></div>}</div>
          <footer className="certificate-footer"><span>Emitida: {new Date(documento.constancia.fechaEmision).toLocaleString('es-PE')}</span><span>Verificación vinculada a la base de datos institucional</span></footer>
        </div>
      </article>
      <div className={`constancia-verification ${verificacion === true ? 'ok' : verificacion === false ? 'fail' : ''}`}>{verificacion === true ? <><ShieldCheck size={17} /> Hash SHA-256 válido: coincide con la matrícula actual.</> : verificacion === false ? <><ShieldX size={17} /> La huella no coincide con los datos actuales.</> : <><Fingerprint size={17} /> Pendiente de verificación en el servidor.</>}</div>
      <div className="constancia-actions"><button type="button" className="constancia-verify" onClick={() => void verificar()} disabled={verificando}>{verificando ? <LoaderCircle className="spin" size={15} /> : <ShieldCheck size={15} />}{verificando ? 'Verificando…' : 'Verificar contra base de datos'}</button><button type="button" className="constancia-print" onClick={() => window.print()}><Printer size={15} /> Imprimir / Guardar como PDF</button></div>
    </> : <div className="constancia-empty"><FileCheck2 size={23} /><span>Se requiere una matrícula confirmada para emitir la constancia.</span></div>}
    <p className="constancia-note"><Download size={13} /> En el diálogo de impresión selecciona “Guardar como PDF”. La huella se recalcula con PDO a partir de la matrícula vigente.</p>
  </section>;
};

export default ConstanciaPanel;
