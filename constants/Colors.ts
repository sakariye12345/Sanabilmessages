import { SchoolConfig } from '../src/config/schoolConfig';

export const Colors = {
    // Light Mode (Default Google Messages)
    primary: SchoolConfig.PRIMARY_COLOR,
    onPrimary: '#ffffff',

    background: '#ffffff',
    surface: '#f0f4f9', // Light gray background for search bar/recipient bubbles
    surfaceVariant: '#e1e3e1',

    textPrimary: '#1f1f1f', // Almost Black
    textSecondary: '#444746', // Dark Gray
    textTimestamp: '#727775',

    // Dark Mode (To be used via context later, but defining tokens here)
    dark: {
        background: '#131314', // Google Dark Background
        surface: '#1e1f20', // Surface containers
        surfaceVariant: '#444746',
        textPrimary: '#e3e3e3',
        textSecondary: '#c4c7c5',
        primary: '#a8c7fa', // Light Blue for Dark Mode
        onPrimary: '#062e6f',
    },

    bubbleInbound: '#f0f4f9',
    bubbleOutbound: SchoolConfig.PRIMARY_COLOR,

    textBubbleInbound: '#1f1f1f',
    textBubbleOutbound: '#ffffff',

    border: '#c4c7c5',

    // Unread indicator
    unread: SchoolConfig.PRIMARY_COLOR,

    // FAB
    fabBackground: SchoolConfig.PRIMARY_COLOR,
    fabContent: '#ffffff',

    // Search Bar
    searchBarBackground: '#f0f4f9',
    searchBarText: '#444746',

    // Error / destructive
    error: '#d93025',
};
