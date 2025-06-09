import bcrypt from "bcryptjs";
import { storage } from "./storage.js";
import { registerSchema, loginSchema } from "../shared/schema.js";

export async function hashPassword(password) {
  return await bcrypt.hash(password, 10);
}

export async function verifyPassword(password, hashedPassword) {
  return await bcrypt.compare(password, hashedPassword);
}

export async function registerUser(data) {
  const validatedData = registerSchema.parse(data);
  
  // Check if user already exists
  const existingUser = await storage.getUserByEmail(validatedData.email);
  if (existingUser) {
    throw new Error("User already exists");
  }
  
  // Hash password
  const hashedPassword = await hashPassword(validatedData.password);
  
  // Create user
  const user = await storage.createUser({
    email: validatedData.email,
    password: hashedPassword,
    firstName: validatedData.firstName,
    lastName: validatedData.lastName,
  });
  
  return user;
}

export async function authenticateUser(data) {
  const validatedData = loginSchema.parse(data);
  
  // Get user by email
  const user = await storage.getUserByEmail(validatedData.email);
  if (!user) {
    throw new Error("Invalid credentials");
  }
  
  // Verify password
  const isValid = await verifyPassword(validatedData.password, user.password);
  if (!isValid) {
    throw new Error("Invalid credentials");
  }
  
  return user;
}