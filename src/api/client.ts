import axios from "axios";
import { useAuthStore } from "../store/auth";

export const BASE_URL = "https://schoolsfls443dr4rsm53m.shihaab.tech";
export const API_TOKEN = "3e8ea952f2a06672";

export const api = axios.create({
  baseURL: BASE_URL,
  headers: {
    "Content-Type": "application/json",
  },
});

// Request interceptor to add auth token if needed
api.interceptors.request.use(async (config) => {
  // For the specific endpoints provided by the user, the token is passed in headers
  // The user provided token "3e8ea952f2a06672" seems to be a static API key for the gateway.
  // We will include it.
  if (API_TOKEN) {
    config.headers.Authorization = API_TOKEN;
  }
  return config;
});
