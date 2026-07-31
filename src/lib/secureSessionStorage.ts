import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from "expo-secure-store";

const CHUNK_SIZE = 1800;

function safeKey(key: string) {
  return key.replace(/[^A-Za-z0-9._-]/g, "_");
}

function metaKey(key: string) {
  return `${safeKey(key)}.meta`;
}

function chunkKey(key: string, index: number) {
  return `${safeKey(key)}.chunk.${index}`;
}

async function readChunkCount(key: string) {
  const rawCount = await SecureStore.getItemAsync(metaKey(key));
  const count = Number(rawCount);
  return Number.isSafeInteger(count) && count > 0 ? count : 0;
}

async function removeSecureValue(key: string) {
  const count = await readChunkCount(key);
  await Promise.all([
    SecureStore.deleteItemAsync(metaKey(key)),
    ...Array.from({ length: count }, (_, index) =>
      SecureStore.deleteItemAsync(chunkKey(key, index))
    ),
  ]);
}

async function writeSecureValue(key: string, value: string) {
  const oldCount = await readChunkCount(key);
  const chunks: string[] = [];
  for (let offset = 0; offset < value.length; offset += CHUNK_SIZE) {
    chunks.push(value.slice(offset, offset + CHUNK_SIZE));
  }
  if (chunks.length === 0) chunks.push("");

  await Promise.all(
    chunks.map((chunk, index) =>
      SecureStore.setItemAsync(chunkKey(key, index), chunk)
    )
  );
  await SecureStore.setItemAsync(metaKey(key), String(chunks.length));

  if (oldCount > chunks.length) {
    await Promise.all(
      Array.from({ length: oldCount - chunks.length }, (_, offset) =>
        SecureStore.deleteItemAsync(chunkKey(key, chunks.length + offset))
      )
    );
  }
}

export const secureSessionStorage = {
  async getItem(key: string) {
    const count = await readChunkCount(key);

    if (count > 0) {
      const chunks = await Promise.all(
        Array.from({ length: count }, (_, index) =>
          SecureStore.getItemAsync(chunkKey(key, index))
        )
      );

      if (chunks.every((chunk) => chunk !== null)) {
        return chunks.join("");
      }

      await removeSecureValue(key);
    }

    // One-time migration from the previous unencrypted AsyncStorage adapter.
    const legacyValue = await AsyncStorage.getItem(key);
    if (!legacyValue) return null;

    await writeSecureValue(key, legacyValue);
    await AsyncStorage.removeItem(key);
    return legacyValue;
  },

  async setItem(key: string, value: string) {
    await writeSecureValue(key, value);
    await AsyncStorage.removeItem(key);
  },

  async removeItem(key: string) {
    await Promise.all([
      removeSecureValue(key),
      AsyncStorage.removeItem(key),
    ]);
  },
};
