export async function getEdgeFunctionErrorMessage(
  error: any,
  fallback: string
) {
  const response = error?.context;

  if (response && typeof response.clone === "function") {
    try {
      const body = await response.clone().json();
      const message = body?.message || body?.error;
      if (typeof message === "string" && message.trim()) {
        return message.trim();
      }
    } catch {
      // The Edge response was not JSON; use the SDK error below.
    }
  }

  const message = error?.message;
  if (
    typeof message === "string" &&
    message.trim() &&
    !message.includes("non-2xx status code")
  ) {
    return message.trim();
  }

  return fallback;
}
