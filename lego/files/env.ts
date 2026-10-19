import dotenv from 'dotenv';

dotenv.config();

function required(name: string): string {
  const value = process.env[name];
  if (value === undefined || value.trim() === '') {
    throw new Error(
      `Variável de ambiente obrigatória ausente: ${name}. Verifique o arquivo .env (use .env.example como modelo).`
    );
  }
  return value;
}

function toInt(name: string, fallback: number): number {
  const raw = process.env[name];
  if (raw === undefined || raw.trim() === '') return fallback;
  const parsed = Number.parseInt(raw, 10);
  if (Number.isNaN(parsed)) {
    throw new Error(`A variável ${name} deve ser um número inteiro (valor atual: "${raw}").`);
  }
  return parsed;
}

export const env = {
  nodeEnv: process.env.NODE_ENV ?? 'development',
  isProduction: process.env.NODE_ENV === 'production',
  port: toInt('PORT', 3001),
  db: {
    host: required('DB_HOST'),
    port: toInt('DB_PORT', 3306),
    user: required('DB_USER'),
    // A senha pode ser vazia em ambiente local, por isso não usa required().
    password: process.env.DB_PASSWORD ?? '',
    name: required('DB_NAME'),
  },
  corsOrigins: (process.env.CORS_ORIGIN ?? 'http://localhost:5173')
    .split(',')
    .map((origin) => origin.trim())
    .filter(Boolean),
} as const;
