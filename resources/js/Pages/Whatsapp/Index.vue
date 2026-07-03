<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Badge from '@/Components/Badge.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { MessageCircle, Send, Phone, Users, CheckCircle2 } from 'lucide-vue-next';

defineProps({ recentMessages: Array, setting: Object });

const templates = [
    { id: 'welcome', name: 'Welcome Message', content: 'Welcome to the Hostel! Your stay has been confirmed. Room: {room}, Check-in: {date}. For any queries, contact us.' },
    { id: 'rent_reminder', name: 'Rent Reminder', content: 'Dear {name}, your hostel fee of Rs {amount} is due on {dueDate}. Please make the payment to avoid late charges.' },
    { id: 'leave_approved', name: 'Leave Approved', content: 'Dear {name}, your leave request from {fromDate} to {toDate} has been approved. Gate pass: {gatePass}.' },
    { id: 'complaint_resolved', name: 'Complaint Resolved', content: 'Dear {name}, your complaint regarding {category} has been resolved. Please confirm.' },
    { id: 'emergency', name: 'Emergency Alert', content: 'EMERGENCY ALERT: {message}. Please take necessary precautions.' },
    { id: 'fee_receipt', name: 'Fee Receipt', content: 'Dear {name}, we have received your payment of Rs {amount}. Receipt: {receipt}. Thank you!' },
];

const form = useForm({ recipient_type: 'individual', recipient_phone: '', content: '' });

const applyTemplate = (e) => {
    const t = templates.find((x) => x.id === e.target.value);
    if (t) form.content = t.content;
};

const send = () => form.post('/whatsapp', { onSuccess: () => { form.reset('content', 'recipient_phone'); } });

const statusColor = { sent: 'green', delivered: 'green', read: 'blue', failed: 'red', scheduled: 'amber' };
</script>

<template>

    <Head title="WhatsApp" />
    <AuthenticatedLayout>
        <template #header>WhatsApp Communication</template>

        <div class="space-y-5">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <MessageCircle class="h-6 w-6 text-blue-600" /> WhatsApp Communication
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">Send messages to residents and parents via WhatsApp</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <Send class="h-4 w-4 text-blue-600" /> Send Message
                    </h2>

                    <form @submit.prevent="send" class="space-y-4">
                        <div>
                            <InputLabel value="Recipient Type" />
                            <div class="flex gap-2">
                                <button type="button" @click="form.recipient_type = 'individual'"
                                    class="px-3 py-1.5 text-sm rounded-lg border flex items-center gap-1.5"
                                    :class="form.recipient_type === 'individual' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                                    <Phone class="h-3.5 w-3.5" /> Individual
                                </button>
                                <button type="button" @click="form.recipient_type = 'all_residents'"
                                    class="px-3 py-1.5 text-sm rounded-lg border flex items-center gap-1.5"
                                    :class="form.recipient_type === 'all_residents' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                                    <Users class="h-3.5 w-3.5" /> All Residents
                                </button>
                                <button type="button" @click="form.recipient_type = 'all_parents'"
                                    class="px-3 py-1.5 text-sm rounded-lg border flex items-center gap-1.5"
                                    :class="form.recipient_type === 'all_parents' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600'">
                                    <Users class="h-3.5 w-3.5" /> All Parents
                                </button>
                            </div>
                        </div>

                        <div v-if="form.recipient_type === 'individual'">
                            <InputLabel value="Phone Number *" />
                            <TextInput v-model="form.recipient_phone" placeholder="+91 98765 43210" />
                        </div>

                        <div>
                            <InputLabel value="Use Template" />
                            <select @change="applyTemplate" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Custom message</option>
                                <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel value="Message *" />
                            <textarea v-model="form.content" rows="5" required
                                class="w-full rounded-lg border-gray-300 text-sm"
                                placeholder="Type your message..."></textarea>
                        </div>

                        <PrimaryButton :disabled="form.processing">
                            {{ form.processing ? 'Sending...' : 'Send Message' }}
                        </PrimaryButton>
                    </form>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-900 mb-4">Gateway Status</h2>
                    <div v-if="setting" class="space-y-2 text-sm">
                        <p class="flex items-center gap-2 text-green-600">
                            <CheckCircle2 class="h-4 w-4" /> {{ setting.status }}
                        </p>
                        <p class="text-gray-500">{{ setting.phone_number }}</p>
                        <p class="text-xs text-gray-400">{{ setting.messages_sent_today }} messages sent today</p>
                    </div>
                    <p v-else class="text-sm text-gray-400">No WhatsApp gateway connected yet. Configure Baileys or
                        another
                        provider to enable live sending.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Recent Messages</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-2">To</th>
                            <th class="text-left px-4 py-2">Message</th>
                            <th class="text-left px-4 py-2">Status</th>
                            <th class="text-left px-4 py-2">Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="m in recentMessages" :key="m.id">
                            <td class="px-4 py-2 text-gray-700">{{ m.recipient_phone }}</td>
                            <td class="px-4 py-2 text-gray-500 truncate max-w-xs">{{ m.content }}</td>
                            <td class="px-4 py-2">
                                <Badge :color="statusColor[m.status]">{{ m.status }}</Badge>
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-400">{{ new Date(m.sent_at).toLocaleString() }}</td>
                        </tr>
                        <tr v-if="!recentMessages.length">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400">No messages sent yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>