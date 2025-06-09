import fs from "fs";
import path from "path";
import { createServer as createViteServer } from "vite";

export function log(message, source = "express") {
  const timestamp = new Date().toLocaleTimeString();
  console.log(`${timestamp} [${source}] ${message}`);
}

export async function setupVite(app, server) {
  const vite = await createViteServer({
    appType: "custom",
    server: {
      middlewareMode: true,
      hmr: { server },
      allowedHosts: true,
    },
  });

  app.use(vite.ssrLoadModule);
  app.use(vite.middlewares);
}

export function serveStatic(app) {
  const distPath = path.resolve("dist");

  if (!fs.existsSync(distPath)) {
    throw new Error(
      `Could not find the build directory: ${distPath}. Please run \`npm run build\` first.`,
    );
  }

  app.use(express.static(distPath));
  app.get("*", (_req, res) => {
    res.sendFile(path.resolve(distPath, "index.html"));
  });
}