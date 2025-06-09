import {
  users,
  services,
  userServices,
  orders,
  supportTickets,
} from "../shared/schema.js";
import { db } from "./db.js";
import { eq, desc } from "drizzle-orm";

export class DatabaseStorage {
  // User operations
  async getUser(id) {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByEmail(email) {
    const [user] = await db.select().from(users).where(eq(users.email, email));
    return user || undefined;
  }

  async createUser(userData) {
    const [user] = await db
      .insert(users)
      .values(userData)
      .returning();
    return user;
  }

  async upsertUser(userData) {
    const [user] = await db
      .insert(users)
      .values(userData)
      .onConflictDoUpdate({
        target: users.id,
        set: {
          ...userData,
          updatedAt: new Date(),
        },
      })
      .returning();
    return user;
  }

  // Service operations
  async getServices() {
    return await db.select().from(services);
  }

  async getService(id) {
    const [service] = await db.select().from(services).where(eq(services.id, id));
    return service || undefined;
  }

  async createService(serviceData) {
    const [service] = await db
      .insert(services)
      .values(serviceData)
      .returning();
    return service;
  }

  // User service operations
  async getUserServices(userId) {
    return await db.select().from(userServices).where(eq(userServices.userId, userId));
  }

  async createUserService(userServiceData) {
    const [userService] = await db
      .insert(userServices)
      .values(userServiceData)
      .returning();
    return userService;
  }

  async updateUserServiceStatus(id, status) {
    await db
      .update(userServices)
      .set({ status, updatedAt: new Date() })
      .where(eq(userServices.id, id));
  }

  // Order operations
  async createOrder(orderData) {
    const [order] = await db
      .insert(orders)
      .values(orderData)
      .returning();
    return order;
  }

  async getOrder(id) {
    const [order] = await db.select().from(orders).where(eq(orders.id, id));
    return order || undefined;
  }

  async getUserOrders(userId) {
    return await db
      .select()
      .from(orders)
      .where(eq(orders.userId, userId))
      .orderBy(desc(orders.createdAt));
  }

  async updateOrderStatus(id, status, paymentId) {
    const updateData = { status, updatedAt: new Date() };
    if (paymentId) {
      updateData.paymentId = paymentId;
    }
    await db
      .update(orders)
      .set(updateData)
      .where(eq(orders.id, id));
  }

  // Support ticket operations
  async createSupportTicket(ticketData) {
    const [ticket] = await db
      .insert(supportTickets)
      .values(ticketData)
      .returning();
    return ticket;
  }

  async getUserSupportTickets(userId) {
    return await db
      .select()
      .from(supportTickets)
      .where(eq(supportTickets.userId, userId))
      .orderBy(desc(supportTickets.createdAt));
  }

  async updateSupportTicketStatus(id, status) {
    await db
      .update(supportTickets)
      .set({ status, updatedAt: new Date() })
      .where(eq(supportTickets.id, id));
  }
}

export const storage = new DatabaseStorage();