import { Router } from 'express';
import healthRoutes from './health.routes';

const router = Router();

router.use('/health', healthRoutes);
// Próximas etapas: /categories, /products, /auth, /cart, /orders, /admin ...

export default router;
