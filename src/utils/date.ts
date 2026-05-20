export const parseMessageDate = (body: string, fallbackDate: string): string => {
    // Try to find "Taariikh: YYYY-MM-DD"
    // Example: "... Taariikh: 2026-01-09. Kala ..."
    const dateRegex = /Taariikh:\s*(\d{4}-\d{2}-\d{2})/;
    const match = body.match(dateRegex);

    if (match && match[1]) {
        // We found a date! 
        // Since we don't have time, we can assume End of Day or Start of Day?
        // User said: "Display the accurate timestamp corresponding to when it was sent"
        // The message body only gives DATE. The API gives `created_at` but user said it's wrong?
        // Actually user said "Display the accurate timestamp...".
        // If the API `created_at` is wrong, and body has valid date, we prefer body date.
        // We'll return it as ISO string. 
        // Let's preserve the time from fallback if possible, or just use T00:00:00
        return new Date(match[1]).toISOString();
    }

    return fallbackDate;
};
