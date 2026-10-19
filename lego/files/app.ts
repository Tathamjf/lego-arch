import cors from 'cors';
import express from 'express';
import helmet from 'helmet';
import { env } from './config/env';
import { errorHandler, notFoundHandler } from './middlewares/errorHandler';
import routes from './routes';

export const app = express();

app.disable('x-powered-by');
app.use(helmet());
app.use(
  cors({
    origin: env.corsOrigins, // só as origens listadas no .env
    credentials: true,
  })
);
app.use(express.json({ limit: '100kb' }));

app.use('/api/v1', routes);

app.use(notFoundHandler);
app.use(errorHandler);
