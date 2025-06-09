import express from "express";
import { createServer } from "http";
import { storage } from "./storage.js";
import { registerUser, authenticateUser } from "./auth.js";
import { insertOrderSchema, insertSupportTicketSchema, registerSchema, loginSchema } from "../shared/schema.js";
import { z } from "zod";
import session from "express-session";
import connectPg from "connect-pg-simple";

export async function registerRoutes(app) {
  // Session configuration
  const sessionTtl = 7 * 24 * 60 * 60 * 1000; // 1 week
  const pgStore = connectPg(session);
  const sessionStore = new pgStore({
    conString: process.env.DATABASE_URL,
    createTableIfMissing: true,
    ttl: sessionTtl,
    tableName: "sessions",
  });

  app.use(session({
    secret: process.env.SESSION_SECRET || 'your-secret-key-here',
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      secure: false, // Set to true in production with HTTPS
      maxAge: sessionTtl,
    },
  }));

  // Auth middleware
  const requireAuth = (req, res, next) => {
    if (!req.session.userId) {
      return res.status(401).json({ message: "Unauthorized" });
    }
    next();
  };

  // Auth routes
  app.post('/api/auth/register', async (req, res) => {
    try {
      const userData = registerSchema.parse(req.body);
      const user = await registerUser(userData);
      
      // Auto-login after registration
      req.session.userId = user.id;
      req.session.user = { id: user.id, email: user.email, firstName: user.firstName, lastName: user.lastName };
      
      res.json({ 
        message: "Registrierung erfolgreich",
        user: { id: user.id, email: user.email, firstName: user.firstName, lastName: user.lastName }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Ungültige Daten", errors: error.errors });
      }
      res.status(400).json({ message: error instanceof Error ? error.message : "Registrierung fehlgeschlagen" });
    }
  });

  app.post('/api/auth/login', async (req, res) => {
    try {
      const loginData = loginSchema.parse(req.body);
      const user = await authenticateUser(loginData);
      
      req.session.userId = user.id;
      req.session.user = { id: user.id, email: user.email, firstName: user.firstName, lastName: user.lastName };
      
      res.json({ 
        message: "Anmeldung erfolgreich",
        user: { id: user.id, email: user.email, firstName: user.firstName, lastName: user.lastName }
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Ungültige Daten", errors: error.errors });
      }
      res.status(401).json({ message: error instanceof Error ? error.message : "Anmeldung fehlgeschlagen" });
    }
  });

  app.post('/api/auth/logout', (req, res) => {
    req.session.destroy(() => {
      res.json({ message: "Abmeldung erfolgreich" });
    });
  });

  app.get('/api/auth/user', requireAuth, async (req, res) => {
    try {
      const user = await storage.getUser(req.session.userId);
      if (!user) {
        return res.status(404).json({ message: "Benutzer nicht gefunden" });
      }
      res.json({ id: user.id, email: user.email, firstName: user.firstName, lastName: user.lastName });
    } catch (error) {
      console.error("Error fetching user:", error);
      res.status(500).json({ message: "Fehler beim Laden der Benutzerdaten" });
    }
  });

  // Services routes
  app.get('/api/services', async (req, res) => {
    try {
      const services = await storage.getServices();
      res.json(services);
    } catch (error) {
      console.error("Error fetching services:", error);
      res.status(500).json({ message: "Fehler beim Laden der Services" });
    }
  });

  app.get('/api/services/:id', async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const service = await storage.getService(id);
      if (!service) {
        return res.status(404).json({ message: "Service nicht gefunden" });
      }
      res.json(service);
    } catch (error) {
      console.error("Error fetching service:", error);
      res.status(500).json({ message: "Fehler beim Laden des Services" });
    }
  });

  // User services routes
  app.get('/api/user/services', requireAuth, async (req, res) => {
    try {
      const userServices = await storage.getUserServices(req.session.userId);
      res.json(userServices);
    } catch (error) {
      console.error("Error fetching user services:", error);
      res.status(500).json({ message: "Fehler beim Laden der Benutzer-Services" });
    }
  });

  // Orders routes
  app.post('/api/orders', requireAuth, async (req, res) => {
    try {
      const orderData = insertOrderSchema.parse({
        ...req.body,
        userId: req.session.userId,
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
        return res.status(400).json({ message: "Ungültige Bestelldaten", errors: error.errors });
      }
      console.error("Error creating order:", error);
      res.status(500).json({ message: "Fehler beim Erstellen der Bestellung" });
    }
  });

  app.get('/api/user/orders', requireAuth, async (req, res) => {
    try {
      const orders = await storage.getUserOrders(req.session.userId);
      res.json(orders);
    } catch (error) {
      console.error("Error fetching user orders:", error);
      res.status(500).json({ message: "Fehler beim Laden der Bestellungen" });
    }
  });

  // Support tickets routes
  app.post('/api/support/tickets', requireAuth, async (req, res) => {
    try {
      const ticketData = insertSupportTicketSchema.parse({
        ...req.body,
        userId: req.session.userId,
      });

      const ticket = await storage.createSupportTicket(ticketData);
      res.json(ticket);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: "Ungültige Ticket-Daten", errors: error.errors });
      }
      console.error("Error creating support ticket:", error);
      res.status(500).json({ message: "Fehler beim Erstellen des Support-Tickets" });
    }
  });

  app.get('/api/user/support/tickets', requireAuth, async (req, res) => {
    try {
      const tickets = await storage.getUserSupportTickets(req.session.userId);
      res.json(tickets);
    } catch (error) {
      console.error("Error fetching support tickets:", error);
      res.status(500).json({ message: "Fehler beim Laden der Support-Tickets" });
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
        console.log('Payment completed:', paymentId);
      }
      
      res.status(200).send('OK');
    } catch (error) {
      console.error("Error processing payment webhook:", error);
      res.status(500).json({ message: "Fehler beim Verarbeiten des Payment-Webhooks" });
    }
  });

  // Server management routes (Mock Proxmox integration)
  app.get('/api/servers/:id/status', requireAuth, async (req, res) => {
    try {
      const serverId = req.params.id;
      const status = await getProxmoxServerStatus(serverId);
      res.json(status);
    } catch (error) {
      console.error("Error fetching server status:", error);
      res.status(500).json({ message: "Fehler beim Laden des Server-Status" });
    }
  });

  app.post('/api/servers/:id/restart', requireAuth, async (req, res) => {
    try {
      const serverId = req.params.id;
      const result = await restartProxmoxServer(serverId);
      res.json(result);
    } catch (error) {
      console.error("Error restarting server:", error);
      res.status(500).json({ message: "Fehler beim Neustart des Servers" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}

// Mock Mollie API functions
async function createMolliePayment(amount, description) {
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

async function getMolliePayment(paymentId) {
  // Mock Mollie payment retrieval
  return {
    id: paymentId,
    status: 'paid',
    amount: { value: '14.99', currency: 'EUR' }
  };
}

// Mock Proxmox API functions
async function getProxmoxServerStatus(serverId) {
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

async function restartProxmoxServer(serverId) {
  // Mock Proxmox server restart
  await new Promise(resolve => setTimeout(resolve, 1000));
  return {
    success: true,
    message: `Server ${serverId} restart initiated`
  };
}