import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Optional but extremely useful while debugging.
// Remove/reduce this in production.
// Pusher.logToConsole = import.meta.env.DEV;

const PUSHER_KEY = import.meta.env.VITE_PUSHER_APP_KEY;
const PUSHER_CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER;

if (!PUSHER_KEY) {
    console.error("[Pusher] VITE_PUSHER_APP_KEY is missing.");
}

if (!PUSHER_CLUSTER) {
    console.error("[Pusher] VITE_PUSHER_APP_CLUSTER is missing.");
}

const echo = new Echo({
    broadcaster: "pusher",

    key: PUSHER_KEY,
    cluster: PUSHER_CLUSTER,

    forceTLS: true,

    authEndpoint: "/broadcasting/auth",

    auth: {
        headers: {
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",

            Accept: "application/json",
        },
    },
});

/*
|--------------------------------------------------------------------------
| Pusher diagnostics
|--------------------------------------------------------------------------
*/

const pusher = echo.connector.pusher;

if (pusher) {
    pusher.connection.bind("state_change", (states) => {
        // console.log(
        //     "[Pusher] State:",
        //     states.previous,
        //     "→",
        //     states.current
        // );
    });

    pusher.connection.bind("connected", () => {
        // console.log(
        //     "[Pusher] Connected. Socket ID:",
        //     pusher.connection.socket_id
        // );
    });

    pusher.connection.bind("error", (error) => {
        console.error("[Pusher] Connection error:", error);
    });

    pusher.connection.bind("failed", () => {
        console.error("[Pusher] Connection failed.");
    });

    pusher.connection.bind("disconnected", () => {
        console.warn("[Pusher] Disconnected.");
    });
}

/**
 * Listen for notifications for the currently logged-in user.
 *
 * Laravel channel:
 *     private-user.{userId}
 *
 * Laravel event:
 *     notification.created
 */
export function listenForNotifications(userId, callback) {
    const channelName = `user.${userId}`;

    // console.log("[Echo] Subscribing to:", channelName);

    const channel = echo
        .private(channelName)
        .subscribed(() => {
            // console.log("[Echo] Successfully subscribed:", channelName);
        })
        .error((error) => {
            console.error(
                "[Echo] Private channel subscription failed:",
                channelName,
                error
            );
        })
        .listen(".notification.created", (payload) => {
            // console.log(
            //     "[Echo] notification.created received:",
            //     payload
            // );

            callback(payload);
        });

    return channel;
}

export function leaveUserChannel(userId) {
    const channelName = `user.${userId}`;

    // console.log("[Echo] Leaving:", channelName);

    echo.leave(channelName);
}

export default echo;