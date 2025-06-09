import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { setupAuth, isAuthenticated } from "./replitAuth";
import { insertOrderSchema, insertSupportTicketSchema } from "@shared/schema";
import { z } from "zod";

export async function registerRoutes(app: Express): Promise<Server> {
  // Auth middleware
  await setupAuth(app);

  // Auth routes
  app.get('/api/auth/user', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const user = await storage.getUser(userId);
      res.json(user);
    } catch (error) {
      console.error("Error fetching user:", error);
      res.status(500).json({ message: "Failed to fetch user" });
    }
  });

  // Services routes
  app.get('/api/services', async (req, res) => {
    try {
      const services = await storage.getServices();
      res.json(services);
    } catch (error) {
      console.error("Error fetching services:", error);
      res.status(500).json({ message: "Failed to fetch services" });
    }
  });

  app.get('/api/services/:id', async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const service = await storage.getService(id);
      if (!service) {
        return res.status(404).json({ message: "Service not found" });
      }
      res.json(service);
    } catch (error) {
      console.error("Error fetching service:", error);
      res.status(500).json({ message: "Failed to fetch service" });
    }
  });

  // User services routes
  app.get('/api/user/services', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const userServices = await storage.getUserServices(userId);
      res.json(userServices);
    } catch (error) {
      console.error("Error fetching user services:", error);
      res.status(500).json({ message: "Failed to fetch user services" });
    }
  });

  // Orders routes
  app.post('/api/orders', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const orderData = insertOrderSchema.parse({
        ...req.body,
        userId,
      });

      const order = await storage.createOrder(orderData);

      // Mock Mollie payment creation
      const molliePayment = await createMolliePayment(order.amount, `Order #${order.id}`);
      
      // Update order with payment ID
      await storage.updateOrderStatus(order.id, "pending", molliePayment.id);

      res.json({
        order,
        checkoutUrl: molliePayment._links?.checkout?.href || "/dashboard",
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid order data", errors: error.errors });
      }
      console.error("Error creating order:", error);
      res.status(500).json({ message: "Failed to create order" });
    }
  });

  app.get('/api/user/orders', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const orders = await storage.getUserOrders(userId);
      res.json(orders);
    } catch (error) {
      console.error("Error fetching user orders:", error);
      res.status(500).json({ message: "Failed to fetch user orders" });
    }
  });

  // Support tickets routes
  app.post('/api/support/tickets', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const ticketData = insertSupportTicketSchema.parse({
        ...req.body,
        userId,
      });

      const ticket = await storage.createSupportTicket(ticketData);
      res.json(ticket);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Invalid ticket data", errors: error.errors });
      }
      console.error("Error creating support ticket:", error);
      res.status(500).json({ message: "Failed to create support ticket" });
    }
  });

  app.get('/api/user/support/tickets', isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const tickets = await storage.getUserSupportTickets(userId);
      res.json(tickets);
    } catch (error) {
      console.error("Error fetching support tickets:", error);
      res.status(500).json({ message: "Failed to fetch support tickets" });
    }
  });

  // Payment webhook (Mollie)
  app.post('/api/webhooks/mollie', async (req, res) => {
    try {
      const { id: paymentId } = req.body;
      
      // Mock payment verification
      const payment = await getMolliePayment(paymentId);
      
      if (payment.status === 'paid') {
        // Find order by payment ID and update status
        const orders = await storage.getUserOrders(''); // Would need to find by payment ID
        // In a real implementation, you'd have a proper way to find the order
        
        // For now, just acknowledge the webhook
        console.log('Payment completed:', paymentId);
      }
      
      res.status(200).send('OK');
    } catch (error) {
      console.error("Error processing payment webhook:", error);
      res.status(500).json({ message: "Failed to process payment webhook" });
    }
  });

  // Server management routes (Mock Proxmox integration)
  app.get('/api/servers/:id/status', isAuthenticated, async (req: any, res) => {
    try {
      const serverId = req.params.id;
      const status = await getProxmoxServerStatus(serverId);
      res.json(status);
    } catch (error) {
      console.error("Error fetching server status:", error);
      res.status(500).json({ message: "Failed to fetch server status" });
    }
  });

  app.post('/api/servers/:id/restart', isAuthenticated, async (req: any, res) => {
    try {
      const serverId = req.params.id;
      const result = await restartProxmoxServer(serverId);
      res.json(result);
    } catch (error) {
      console.error("Error restarting server:", error);
      res.status(500).json({ message: "Failed to restart server" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}

// Mock Mollie API functions
async function createMolliePayment(amount: string, description: string) {
  // Mock Mollie payment creation
  return {
    id: `tr_${Date.now()}`,
    status: 'open',
    amount: { value: amount, currency: 'EUR' },
    description,
    _links: {
      checkout: {
        href: `https://checkout.mollie.com/payment/${Date.now()}`,
        type: 'text/html'
      }
    }
  };
}

async function getMolliePayment(paymentId: string) {
  // Mock Mollie payment retrieval
  return {
    id: paymentId,
    status: 'paid',
    amount: { value: '14.99', currency: 'EUR' }
  };
}

// Mock Proxmox API functions
async function getProxmoxServerStatus(serverId: string) {
  // Mock Proxmox server status
  return {
    vmid: serverId,
    status: 'running',
    uptime: Math.floor(Math.random() * 86400),
    cpu: Math.random() * 100,
    memory: Math.random() * 100,
    disk: Math.random() * 100,
    netout: Math.floor(Math.random() * 1000000),
    netin: Math.floor(Math.random() * 1000000)
  };
}

async function restartProxmoxServer(serverId: string) {
  // Mock Proxmox server restart
  await new Promise(resolve => setTimeout(resolve, 1000));
  return {
    success: true,
    message: `Server ${serverId} restart initiated`
  };
}
