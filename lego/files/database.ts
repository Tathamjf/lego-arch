import mysql from 'mysql2';
import type { RowDataPacket } from 'mysql2';
import { env } from './env';

/**
 * Pool de conexões MySQL.
 * - utf8mb4 para acentos/emoji.
 * - DECIMAL volta como string (padrão do mysql2), evitando erro de ponto flutuante em valores monetários.
 * - sql_mode estrito em TODA conexão: como o MySQL 5.6 do UsbWebServer não aplica CHECK,
 *   o modo estrito faz valores inválidos (ex.: estoque UNSIGNED negativo) gerarem erro.
 */
const corePool = mysql.createPool({
  host: env.db.host,
  port: env.db.port,
  user: env.db.user,
  password: env.db.password,
  database: env.db.name,
  charset: 'utf8mb4',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
});

corePool.on('connection', (connection) => {
  connection.query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
});

export const pool = corePool.promise();

interface VersionRow extends RowDataPacket {
  version: string;
}

/** Testa a conexão e devolve a versão do servidor MySQL. */
export async function checkDatabase(): Promise<string> {
  const [rows] = await pool.query<VersionRow[]>('SELECT VERSION() AS version');
  return rows[0].version;
}
