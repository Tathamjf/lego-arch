import { app } from './app';
import { checkDatabase } from './config/database';
import { env } from './config/env';

async function start(): Promise<void> {
  try {
    const version = await checkDatabase();
    console.log(`MySQL conectado (versão ${version}) no banco "${env.db.name}".`);
  } catch (error) {
    console.error('Não foi possível conectar ao MySQL. Verifique se o UsbWebServer está iniciado e se o .env está correto.');
    console.error(error);
    process.exit(1);
  }

  app.listen(env.port, () => {
    console.log(`API rodando em http://localhost:${env.port}/api/v1 (${env.nodeEnv})`);
  });
}

void start();
