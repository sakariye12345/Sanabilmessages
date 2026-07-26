import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, Animated } from 'react-native';
import NetInfo from '@react-native-community/netinfo';
import { Colors } from '../../constants/Colors';

export function OfflineBanner() {
    const [isOffline, setIsOffline] = useState(false);
    const [heightAnim] = useState(new Animated.Value(0));

    useEffect(() => {
        const unsubscribe = NetInfo.addEventListener((state) => {
            const offline = state.isConnected === false;
            setIsOffline(offline);

            Animated.timing(heightAnim, {
                toValue: offline ? 40 : 0,
                duration: 300,
                useNativeDriver: false, // height is not supported by native driver
            }).start();
        });

        return () => unsubscribe();
    }, []);

    return (
        <Animated.View
            pointerEvents={isOffline ? 'auto' : 'none'}
            style={[s.container, { height: heightAnim }]}
        >
            <Text style={s.text}>No Internet Connection</Text>
        </Animated.View>
    );
}

const s = StyleSheet.create({
    container: {
        backgroundColor: '#D32F2F', // Red error color
        justifyContent: 'center',
        alignItems: 'center',
        overflow: 'hidden',
        width: '100%',
    },
    text: {
        color: '#FFFFFF',
        fontSize: 14,
        fontWeight: '600',
    },
});
