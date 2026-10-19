import { Router } from 'express';
import { checkDatabase } from '../config/database';
import { sendSuccess } from '../utils/response';

const router = Router();

// GET /api/v1/health  ->  confirma que a API e o MySQL estão respondendo
router.get('/', async (_req, res) => {
  const mysqlVersion = await checkDatabase();
  sendSuccess(res, {
    status: 'ok',
    database: { connected: true, version: mysqlVersion },
    timestamp: new Date().toISOString(),
  });
});

export default router;
