import bcrypt from "bcryptjs";
import { storage } from "./storage";
import { registerSchema, loginSchema, type User } from "@shared/schema";
import { z } from "zod";

export async function hashPassword(password: string): Promise<string> {
  return await bcrypt.hash(password, 12);
}

export async function verifyPassword(password: string, hashedPassword: string): Promise<boolean> {
  return await bcrypt.compare(password, hashedPassword);
}

export async function registerUser(data: z.infer<typeof registerSchema>): Promise<User> {
  // Check if user already exists
  const existingUser = await storage.getUserByEmail(data.email);
  if (existingUser) {
    throw new Error("Ein Benutzer mit dieser E-Mail-Adresse existiert bereits");
  }

  // Hash password
  const hashedPassword = await hashPassword(data.password);

  // Create user
  const user = await storage.createUser({
    email: data.email,
    password: hashedPassword,
    firstName: data.firstName,
    lastName: data.lastName,
  });

  return user;
}

export async function authenticateUser(data: z.infer<typeof loginSchema>): Promise<User> {
  // Find user by email
  const user = await storage.getUserByEmail(data.email);
  if (!user) {
    throw new Error("Ungültige E-Mail-Adresse oder Passwort");
  }

  // Verify password
  const isValidPassword = await verifyPassword(data.password, user.password);
  if (!isValidPassword) {
    throw new Error("Ungültige E-Mail-Adresse oder Passwort");
  }

  return user;
}