import type { Response } from 'express';

/** Formato padrão de sucesso: { success: true, data } */
export function sendSuccess<T>(res: Response, data: T, statusCode = 200): void {
  res.status(statusCode).json({ success: true, data });
}
