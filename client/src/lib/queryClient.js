import { QueryClient } from "@tanstack/react-query";

async function throwIfResNotOk(res) {
  if (!res.ok) {
    const error = await res.text();
    throw new Error(`${res.status}: ${error}`);
  }
}

export async function apiRequest(method, url, body) {
  const options = {
    method,
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  };

  if (body) {
    options.body = JSON.stringify(body);
  }

  const res = await fetch(url, options);
  await throwIfResNotOk(res);
  return res.json();
}

export const getQueryFn = ({ on401 }) => {
  return async ({ queryKey }) => {
    const url = Array.isArray(queryKey) ? queryKey[0] : queryKey;
    try {
      const res = await fetch(url, {
        credentials: "include",
      });
      if (res.status === 401) {
        if (on401 === "returnNull") {
          return null;
        } else {
          throw new Error("401: Unauthorized");
        }
      }
      await throwIfResNotOk(res);
      return res.json();
    } catch (error) {
      if (error.message.includes("401") && on401 === "returnNull") {
        return null;
      }
      throw error;
    }
  };
};

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      queryFn: getQueryFn({ on401: "throw" }),
      retry: (failureCount, error) => {
        if (error?.message?.includes("401") || error?.message?.includes("403")) {
          return false;
        }
        return failureCount < 3;
      },
    },
  },
});