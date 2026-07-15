<!-- resources/js/Pages/Registration/Form.vue -->
<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, nextTick } from "vue";
import {
    User,
    Phone,
    MapPin,
    GraduationCap,
    Home,
    Heart,
    Trophy,
    Shield,
    Camera,
    CreditCard,
    Banknote,
    ChevronLeft,
    ChevronRight,
    Check,
    AlertCircle,
    Upload,
} from "lucide-vue-next";

const props = defineProps({
    lang: { type: String, default: "en" },
    razorpayKey: String,
    registrationFee: { type: Number, default: 300 },
});

const currentStep = ref(1);
const totalSteps = 5;
const isProcessing = ref(false);
const paymentError = ref("");

const fieldSteps = {
    student_photo: 1,
    student_name: 1,
    father_name: 1,
    mother_name: 1,
    dob: 1,
    age: 1,
    student_mobile: 1,
    father_mobile: 1,
    mother_mobile: 1,
    email: 1,
    permanent_address: 1,
    current_address: 1,
    father_photo: 1,
    mother_photo: 1,

    institution_name: 2,
    institution_address: 2,
    course_name: 2,
    course_duration: 2,
    room_type: 2,
    stay_duration_from: 2,
    stay_duration_to: 2,
    institution_contact1: 2,
    institution_contact2: 2,

    has_driving_license: 3,
    vehicle_type: 3,
    vehicle_number: 3,
    disease_history: 3,
    allergy_details: 3,
    special_achievements: 3,

    guardian1_name: 4,
    guardian1_mobile: 4,
    guardian1_occupation: 4,
    guardian1_address: 4,
    guardian2_name: 4,
    guardian2_mobile: 4,
    guardian2_occupation: 4,
    guardian2_address: 4,
    guardian_photo: 4,
    family_photo1: 4,
    family_photo2: 4,

    payment_method: 5,
};

const fieldLabels = computed(() => ({
    student_photo: t.value.studentPhoto.replace(" *", ""),
    student_name: t.value.studentName.replace(" *", ""),
    father_name: t.value.fatherName.replace(" *", ""),
    mother_name: t.value.motherName.replace(" *", ""),
    dob: t.value.dob.replace(" *", ""),
    age: t.value.age.replace(" *", ""),
    student_mobile: t.value.studentMobile.replace(" *", ""),
    father_mobile: t.value.fatherMobile,
    mother_mobile: t.value.motherMobile,
    email: t.value.email,
    permanent_address: t.value.permanentAddress.replace(" *", ""),
    current_address: t.value.currentAddress,
    father_photo: t.value.fatherPhoto,
    mother_photo: t.value.motherPhoto,

    institution_name: t.value.institutionName.replace(" *", ""),
    institution_address: t.value.institutionAddress,
    course_name: t.value.courseName.replace(" *", ""),
    course_duration: t.value.courseDuration.replace(" *", ""),
    room_type: t.value.roomType.replace(" *", ""),
    stay_duration_from: `${t.value.stayDuration} (${t.value.from})`,
    stay_duration_to: `${t.value.stayDuration} (${t.value.to})`,
    institution_contact1: t.value.contact1,
    institution_contact2: t.value.contact2,

    vehicle_type: t.value.vehicleType,
    vehicle_number: t.value.vehicleNumber,
    disease_history: t.value.diseaseHistory,
    allergy_details: t.value.allergyDetails,
    special_achievements: t.value.achievements,

    guardian1_name: `${t.value.guardianName} 1`,
    guardian1_mobile: `${t.value.guardianMobile} 1`,
    guardian1_occupation: `${t.value.guardianOccupation} 1`,
    guardian1_address: `${t.value.guardianAddress} 1`,
    guardian2_name: `${t.value.guardianName} 2`,
    guardian2_mobile: `${t.value.guardianMobile} 2`,
    guardian2_occupation: `${t.value.guardianOccupation} 2`,
    guardian2_address: `${t.value.guardianAddress} 2`,
    guardian_photo: t.value.guardianPhoto,
    family_photo1: `${t.value.familyPhotos} 1`,
    family_photo2: `${t.value.familyPhotos} 2`,

    payment_method: t.value.paymentMethod.replace(" *", ""),
}));

const validationSummary = computed(() =>
    Object.entries(form.errors)
        .map(([field, message]) => ({
            field,
            message,
            label: fieldLabels.value[field] ?? field.replaceAll("_", " "),
            step: fieldSteps[field] ?? 5,
        }))
        .sort((a, b) => a.step - b.step),
);

const stepErrors = computed(() => {
    const counts = {};

    validationSummary.value.forEach(({ step }) => {
        counts[step] = (counts[step] ?? 0) + 1;
    });

    return counts;
});

const validationTitle = computed(() =>
    props.lang === "hi"
        ? "आवेदन जमा करने से पहले इन त्रुटियों को सुधारें"
        : "Please correct these errors before submitting",
);

const validationHelp = computed(() =>
    props.lang === "hi"
        ? "किसी त्रुटि पर क्लिक करके संबंधित चरण और फील्ड पर जाएँ।"
        : "Click an error to open its step and focus the related field.",
);

const jumpToField = async (item) => {
    currentStep.value = item.step;
    await nextTick();

    const element = document.getElementById(item.field);
    if (!element) return;

    element.scrollIntoView({ behavior: "smooth", block: "center" });

    if (typeof element.focus === "function") {
        element.focus({ preventScroll: true });
    }
};

const showValidationSummary = async () => {
    currentStep.value = totalSteps;
    await nextTick();

    document.getElementById("validation-summary")?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
};

const scrollToValidationSummary = () => {
    document.getElementById("validation-summary")?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
};

const t = computed(() => ({
    // Page Title
    title: props.lang === "hi" ? "आवेदन-पत्र" : "Application Form",
    subtitle:
        props.lang === "hi"
            ? "प्रतिभा-प्रतीक्षा छात्रावास - आवेदन पत्र"
            : "Pratibha-Pratiksha Hostel - Application Form",

    // Steps
    steps:
        props.lang === "hi"
            ? [
                  "व्यक्तिगत जानकारी",
                  "शैक्षणिक विवरण",
                  "स्वास्थ्य और वाहन",
                  "संरक्षक विवरण",
                  "भुगतान",
              ]
            : [
                  "Personal Details",
                  "Education Details",
                  "Health & Vehicle",
                  "Guardian Details",
                  "Payment",
              ],

    // Personal Details
    studentName:
        props.lang === "hi" ? "छात्र/छात्रा का नाम *" : "Student Name *",
    fatherName: props.lang === "hi" ? "पिता का नाम *" : "Father's Name *",
    motherName: props.lang === "hi" ? "माता का नाम *" : "Mother's Name *",
    dob: props.lang === "hi" ? "जन्म तिथि *" : "Date of Birth *",
    age: props.lang === "hi" ? "उम्र *" : "Age *",
    bloodGroup: props.lang === "hi" ? "रक्त समूह" : "Blood Group",
    studentMobile:
        props.lang === "hi" ? "छात्र का मोबाइल नंबर *" : "Student Mobile No. *",
    fatherMobile:
        props.lang === "hi" ? "पिता का मोबाइल नंबर" : "Father's Mobile No.",
    motherMobile:
        props.lang === "hi" ? "माता का मोबाइल नंबर" : "Mother's Mobile No.",
    email: props.lang === "hi" ? "ईमेल" : "Email",
    permanentAddress:
        props.lang === "hi" ? "स्थायी पता *" : "Permanent Address *",
    currentAddress: props.lang === "hi" ? "वर्तमान पता" : "Current Address",
    studentPhoto:
        props.lang === "hi" ? "छात्र/छात्रा का फोटो *" : "Student Photo *",
    fatherPhoto: props.lang === "hi" ? "पिता का फोटो" : "Father's Photo",
    motherPhoto: props.lang === "hi" ? "माता का फोटो" : "Mother's Photo",

    // Education
    institutionName:
        props.lang === "hi" ? "संस्थान का नाम *" : "Institution Name *",
    institutionAddress:
        props.lang === "hi" ? "संस्थान का पता" : "Institution Address",
    courseName: props.lang === "hi" ? "पाठ्यक्रम का नाम *" : "Course Name *",
    courseDuration:
        props.lang === "hi" ? "पाठ्यक्रम की अवधि *" : "Course Duration *",
    roomType: props.lang === "hi" ? "कमरे का प्रकार *" : "Room Type *",
    stayDuration:
        props.lang === "hi"
            ? "छात्रावास में रुकने की अवधि"
            : "Hostel Stay Duration",
    from: props.lang === "hi" ? "से" : "From",
    to: props.lang === "hi" ? "तक" : "To",
    contact1:
        props.lang === "hi"
            ? "शैक्षणिक संस्थान का संपर्क सूत्र 1"
            : "Institution Contact 1",
    contact2:
        props.lang === "hi"
            ? "शैक्षणिक संस्थान का संपर्क सूत्र 2"
            : "Institution Contact 2",

    // Room types
    twoSeater: props.lang === "hi" ? "2 सीटर" : "2-Seater",
    threeSeater: props.lang === "hi" ? "3 सीटर" : "3-Seater",
    fourSeater: props.lang === "hi" ? "4 सीटर" : "4-Seater",

    // Health & Vehicle
    drivingLicense:
        props.lang === "hi"
            ? "क्या आपके पास ड्राइविंग लाइसेंस है?"
            : "Do you have a Driving License?",
    yes: props.lang === "hi" ? "हाँ" : "Yes",
    no: props.lang === "hi" ? "नहीं" : "No",
    vehicleType: props.lang === "hi" ? "वाहन का प्रकार" : "Vehicle Type",
    vehicleNumber: props.lang === "hi" ? "वाहन नंबर" : "Vehicle Number",
    twoWheeler: props.lang === "hi" ? "टू-व्हीलर" : "Two Wheeler",
    fourWheeler: props.lang === "hi" ? "फोर-व्हीलर" : "Four Wheeler",
    diseaseHistory:
        props.lang === "hi"
            ? "यदि किसी गंभीर बीमारी से पीड़ित हों तो विवरण दें"
            : "Any serious disease history",
    allergyDetails:
        props.lang === "hi"
            ? "यदि किसी चीज से एलर्जी हो तो विवरण दें"
            : "Any allergy details",
    achievements:
        props.lang === "hi"
            ? "शैक्षणिक, खेलकूद आदि में विशेष उपलब्धियाँ"
            : "Special achievements in education, sports etc.",

    // Guardians
    guardianDetails:
        props.lang === "hi"
            ? "स्थानीय संरक्षक का विवरण"
            : "Local Guardian Details",
    guardianName: props.lang === "hi" ? "संरक्षक का नाम" : "Guardian Name",
    guardianMobile: props.lang === "hi" ? "मोबाइल नंबर" : "Mobile No.",
    guardianOccupation: props.lang === "hi" ? "व्यवसाय" : "Occupation",
    guardianAddress: props.lang === "hi" ? "पूरा पता" : "Full Address",
    guardianPhoto: props.lang === "hi" ? "संरक्षक का फोटो" : "Guardian Photo",
    familyPhotos:
        props.lang === "hi"
            ? "परिवार के सदस्यों के फोटो"
            : "Family Members Photos",

    // Payment
    paymentMethod:
        props.lang === "hi" ? "भुगतान का तरीका *" : "Payment Method *",
    onlinePayment:
        props.lang === "hi"
            ? "ऑनलाइन भुगतान (Razorpay)"
            : "Online Payment (Razorpay)",
    cashPayment:
        props.lang === "hi"
            ? "नकद भुगतान (कार्यालय में)"
            : "Cash Payment (At Office)",
    registrationFee: props.lang === "hi" ? "पंजीकरण शुल्क" : "Registration Fee",
    payNow: props.lang === "hi" ? "अब भुगतान करें" : "Pay Now",
    submitApplication:
        props.lang === "hi" ? "आवेदन जमा करें" : "Submit Application",

    // Declaration
    declaration:
        props.lang === "hi"
            ? "हम शपथ पूर्वक वचन देते हैं कि उपरोक्त संपूर्ण जानकारी हमारे द्वारा प्रदान की गई है वह पूर्णरूपेण सत्य है। हमने आपके द्वारा निर्धारित समस्त शर्तें एवं नियमों का अध्ययन कर लिया है जो हमें पूर्णतः मान्य है तथा भविष्य में भी जो नियम /शर्ते आप निर्धारित करेंगे हमें मान्य रहेंगे, हमें आपके छात्रावास में प्रवेश प्रदान करने का कष्ट करें। अगर हमारे द्वारा छात्रावास के नियमों का उल्लंघन या अनुशासन हीनता होने पर आप बिना सूचना तथा अमानत राशि वापस दिए बिना छात्रावास से निष्कासित कर सकते हैं। छात्रावास छोड़ने के एक माह पूर्व हम लिखित सुचना देंगे अन्यथा जमा वापसी योग्य राशि प्राप्त करने के अधिकारी नहीं रहेंगे।"
            : "I solemnly declare that all the above information provided by me is true and correct.",

    // Navigation
    next: props.lang === "hi" ? "आगे" : "Next",
    previous: props.lang === "hi" ? "पीछे" : "Previous",
    submit: props.lang === "hi" ? "जमा करें" : "Submit",

    // Errors
    required:
        props.lang === "hi" ? "यह फील्ड आवश्यक है" : "This field is required",
}));

const form = useForm({
    // Personal
    student_name: "",
    father_name: "",
    mother_name: "",
    dob: "",
    age: "",
    blood_group: "",
    student_mobile: "",
    father_mobile: "",
    mother_mobile: "",
    email: "",
    permanent_address: "",
    current_address: "",

    // Education
    institution_name: "",
    institution_address: "",
    course_name: "",
    course_duration: "",
    room_type: "2-seater",
    stay_duration_from: "",
    stay_duration_to: "",
    institution_contact1: "",
    institution_contact2: "",

    // Health & Vehicle
    has_driving_license: false,
    vehicle_type: "",
    vehicle_number: "",
    disease_history: "",
    allergy_details: "",
    special_achievements: "",

    // Guardians
    guardian1_name: "",
    guardian1_mobile: "",
    guardian1_occupation: "",
    guardian1_address: "",
    guardian2_name: "",
    guardian2_mobile: "",
    guardian2_occupation: "",
    guardian2_address: "",

    // Files
    student_photo: null,
    father_photo: null,
    mother_photo: null,
    family_photo1: null,
    family_photo2: null,
    guardian_photo: null,

    // Payment
    payment_method: "razorpay",
    razorpay_order_id: "",
    razorpay_payment_id: "",
    razorpay_signature: "",
});

const photoPreviews = ref({
    student_photo: null,
    father_photo: null,
    mother_photo: null,
    family_photo1: null,
    family_photo2: null,
    guardian_photo: null,
});

const handlePhotoChange = (field, event) => {
    const file = event.target.files[0];
    if (file) {
        form[field] = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreviews.value[field] = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const calculateAge = () => {
    if (form.dob) {
        const birthDate = new Date(form.dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (
            monthDiff < 0 ||
            (monthDiff === 0 && today.getDate() < birthDate.getDate())
        ) {
            age--;
        }
        form.age = age;
    }
};

const nextStep = () => {
    if (currentStep.value < totalSteps) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const validateBeforeSubmit = () => {
    form.clearErrors();

    const requiredFields = {
        student_photo: form.student_photo,
        student_name: form.student_name,
        father_name: form.father_name,
        mother_name: form.mother_name,
        dob: form.dob,
        age: form.age,
        student_mobile: form.student_mobile,
        permanent_address: form.permanent_address,
        institution_name: form.institution_name,
        course_name: form.course_name,
        course_duration: form.course_duration,
        room_type: form.room_type,
        payment_method: form.payment_method,
    };

    Object.entries(requiredFields).forEach(([field, value]) => {
        if (
            value === null ||
            value === undefined ||
            String(value).trim() === ""
        ) {
            form.setError(field, t.value.required);
        }
    });

    if (form.email) {
        form.email = form.email.trim();

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(form.email)) {
            form.setError(
                "email",
                props.lang === "hi"
                    ? "कृपया वैध ईमेल दर्ज करें"
                    : "Please enter a valid email address",
            );
        }
    }

    if (
        form.stay_duration_from &&
        form.stay_duration_to &&
        new Date(`${form.stay_duration_to}T00:00:00`) <=
            new Date(`${form.stay_duration_from}T00:00:00`)
    ) {
        form.setError(
            "stay_duration_to",
            props.lang === "hi"
                ? "समाप्ति तिथि प्रारंभ तिथि के बाद होनी चाहिए"
                : "The end date must be after the start date",
        );
    }

    if (form.has_driving_license && !form.vehicle_type) {
        form.setError(
            "vehicle_type",
            props.lang === "hi"
                ? "वाहन का प्रकार चुनें"
                : "Please select a vehicle type",
        );
    }

    if (form.has_driving_license && !form.vehicle_number?.trim()) {
        form.setError(
            "vehicle_number",
            props.lang === "hi"
                ? "वाहन नंबर दर्ज करें"
                : "Please enter the vehicle number",
        );
    }

    return !form.hasErrors;
};

const initRazorpay = async () => {
    isProcessing.value = true;
    paymentError.value = "";

    try {
        // Create order
        const response = await fetch(route("razorpay.order"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({ amount: props.registrationFee }),
        });

        const orderData = await response.json();

        if (!orderData.success) {
            throw new Error(orderData.message);
        }

        const options = {
            key: props.razorpayKey,
            amount: orderData.amount,
            currency: orderData.currency,
            name: "Pratibha-Pratiksha Hostel",
            description: "Registration Fee",
            order_id: orderData.order_id,
            handler: function (response) {
                form.razorpay_order_id = response.razorpay_order_id;
                form.razorpay_payment_id = response.razorpay_payment_id;
                form.razorpay_signature = response.razorpay_signature;
                submitForm();
            },
            prefill: {
                name: form.student_name,
                email: form.email,
                contact: form.student_mobile,
            },
            theme: {
                color: "#4f46e5",
            },
            modal: {
                ondismiss: function () {
                    isProcessing.value = false;
                },
            },
        };

        const rzp = new Razorpay(options);
        rzp.open();

        rzp.on("payment.failed", function (response) {
            paymentError.value = response.error.description;
            isProcessing.value = false;
        });
    } catch (error) {
        paymentError.value = error.message;
        isProcessing.value = false;
    }
};

const submitForm = () => {
    form.post(route("register.store"), {
        onSuccess: () => {
            isProcessing.value = false;
        },
        onError: async () => {
            isProcessing.value = false;
            await showValidationSummary();
        },
    });
};

const handleSubmit = async () => {
    paymentError.value = "";

    if (!validateBeforeSubmit()) {
        await showValidationSummary();
        return;
    }

    if (form.payment_method === "razorpay") {
        initRazorpay();
    } else {
        submitForm();
    }
};
</script>

<template>
    <Head :title="t.title" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">
                            {{ t.title }}
                        </h1>
                        <p class="text-sm text-gray-500">{{ t.subtitle }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            :href="
                                route('register.form', {
                                    lang: lang === 'en' ? 'hi' : 'en',
                                })
                            "
                            class="px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
                        >
                            {{ lang === "en" ? "हिंदी" : "English" }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div
                        v-for="step in totalSteps"
                        :key="step"
                        class="flex items-center"
                    >
                        <button
                            type="button"
                            class="relative flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition-colors"
                            :class="[
                                step <= currentStep
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-200 text-gray-500',
                                stepErrors[step]
                                    ? 'ring-2 ring-red-300 ring-offset-2'
                                    : '',
                            ]"
                            @click="currentStep = step"
                        >
                            <Check
                                v-if="step < currentStep && !stepErrors[step]"
                                class="h-4 w-4"
                            />
                            <span v-else>{{ step }}</span>

                            <span
                                v-if="stepErrors[step]"
                                class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
                            >
                                {{ stepErrors[step] }}
                            </span>
                        </button>
                        <div
                            v-if="step < totalSteps"
                            class="w-16 h-0.5 mx-2"
                            :class="
                                step < currentStep
                                    ? 'bg-indigo-600'
                                    : 'bg-gray-200'
                            "
                        />
                    </div>
                </div>
                <div class="flex justify-between mt-2">
                    <span
                        v-for="(label, idx) in t.steps"
                        :key="idx"
                        class="text-xs text-center w-20"
                        :class="
                            idx + 1 === currentStep
                                ? 'text-indigo-600 font-medium'
                                : 'text-gray-400'
                        "
                    >
                        {{ label }}
                    </span>
                </div>
            </div>

            <!-- Global validation summary: visible from every step -->
            <div
                v-if="validationSummary.length"
                id="validation-summary"
                class="mb-6 scroll-mt-28 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100"
                    >
                        <AlertCircle class="h-5 w-5 text-red-600" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <div>
                                <h2 class="text-sm font-semibold text-red-800">
                                    {{ validationTitle }}
                                </h2>
                                <p class="mt-0.5 text-xs text-red-600">
                                    {{ validationHelp }}
                                </p>
                            </div>

                            <span
                                class="rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white"
                            >
                                {{ validationSummary.length }}
                                {{
                                    lang === "hi"
                                        ? "त्रुटि"
                                        : validationSummary.length === 1
                                          ? "error"
                                          : "errors"
                                }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <button
                                v-for="item in validationSummary"
                                :key="item.field"
                                type="button"
                                class="flex min-w-0 items-start gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-left transition hover:border-red-300 hover:bg-red-100"
                                @click="jumpToField(item)"
                            >
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[10px] font-bold text-red-700"
                                >
                                    {{ item.step }}
                                </span>
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-xs font-semibold text-red-800"
                                    >
                                        {{ t.steps[item.step - 1] }} ·
                                        {{ item.label }}
                                    </span>
                                    <span
                                        class="mt-0.5 block text-xs text-red-600"
                                    >
                                        {{ item.message }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6" novalidate>
                <!-- Step 1: Personal Details -->
                <div
                    v-show="currentStep === 1"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                >
                    <div
                        class="flex items-center gap-2 pb-4 border-b border-gray-100"
                    >
                        <User class="w-5 h-5 text-indigo-600" />
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t.steps[0] }}
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Student Photo -->
                        <div class="md:col-span-2 flex justify-center">
                            <div class="text-center">
                                <div
                                    class="relative w-32 h-40 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                                >
                                    <img
                                        v-if="photoPreviews.student_photo"
                                        :src="photoPreviews.student_photo"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center h-full text-gray-400"
                                    >
                                        <Camera class="w-8 h-8 mb-1" />
                                        <span class="text-xs">Photo</span>
                                    </div>
                                </div>
                                <label class="cursor-pointer">
                                    <span
                                        class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
                                        >{{ t.studentPhoto }}</span
                                    >
                                    <input
                                        id="student_photo"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="
                                            (e) =>
                                                handlePhotoChange(
                                                    'student_photo',
                                                    e,
                                                )
                                        "
                                    />
                                </label>
                                <p
                                    v-if="form.errors.student_photo"
                                    class="text-xs text-red-500 mt-1"
                                >
                                    {{ form.errors.student_photo }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.studentName }}</label
                            >
                            <input
                                id="student_name"
                                v-model="form.student_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.student_name"
                                class="text-xs text-red-500 mt-1"
                            >
                                {{ form.errors.student_name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.fatherName }}</label
                            >
                            <input
                                id="father_name"
                                v-model="form.father_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.father_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.father_name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.motherName }}</label
                            >
                            <input
                                id="mother_name"
                                v-model="form.mother_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.mother_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.mother_name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.dob }}</label
                            >
                            <input
                                id="dob"
                                v-model="form.dob"
                                type="date"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                                @change="calculateAge"
                            />
                            <p
                                v-if="form.errors.dob"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.dob }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.age }}</label
                            >
                            <input
                                id="age"
                                v-model="form.age"
                                type="number"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                                readonly
                            />
                            <p
                                v-if="form.errors.age"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.age }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.bloodGroup }}</label
                            >
                            <select
                                id="blood_group"
                                v-model="form.blood_group"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.studentMobile }}</label
                            >
                            <input
                                id="student_mobile"
                                v-model="form.student_mobile"
                                type="tel"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.student_mobile"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.student_mobile }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.fatherMobile }}</label
                            >
                            <input
                                id="father_mobile"
                                v-model="form.father_mobile"
                                type="tel"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.motherMobile }}</label
                            >
                            <input
                                id="mother_mobile"
                                v-model="form.mother_mobile"
                                type="tel"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.email }}</label
                            >
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p
                                v-if="form.errors.email"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t.permanentAddress }}</label
                        >
                        <textarea
                            id="permanent_address"
                            v-model="form.permanent_address"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        ></textarea>
                        <p
                            v-if="form.errors.permanent_address"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.permanent_address }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >{{ t.currentAddress }}</label
                        >
                        <textarea
                            id="current_address"
                            v-model="form.current_address"
                            rows="3"
                            class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <!-- Parent Photos -->
                    <div
                        class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100"
                    >
                        <div class="text-center">
                            <div
                                class="relative w-24 h-32 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                            >
                                <img
                                    v-if="photoPreviews.father_photo"
                                    :src="photoPreviews.father_photo"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center h-full text-gray-400"
                                >
                                    <Camera class="w-6 h-6" />
                                </div>
                            </div>
                            <label class="cursor-pointer">
                                <span
                                    class="text-xs text-indigo-600 hover:text-indigo-700"
                                    >{{ t.fatherPhoto }}</span
                                >
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    id="father_photo"
                                    @change="
                                        (e) =>
                                            handlePhotoChange('father_photo', e)
                                    "
                                />
                            </label>
                        </div>
                        <div class="text-center">
                            <div
                                class="relative w-24 h-32 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                            >
                                <img
                                    v-if="photoPreviews.mother_photo"
                                    :src="photoPreviews.mother_photo"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center h-full text-gray-400"
                                >
                                    <Camera class="w-6 h-6" />
                                </div>
                            </div>
                            <label class="cursor-pointer">
                                <span
                                    class="text-xs text-indigo-600 hover:text-indigo-700"
                                    >{{ t.motherPhoto }}</span
                                >
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    id="mother_photo"
                                    @change="
                                        (e) =>
                                            handlePhotoChange('mother_photo', e)
                                    "
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Education Details -->
                <div
                    v-show="currentStep === 2"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                >
                    <div
                        class="flex items-center gap-2 pb-4 border-b border-gray-100"
                    >
                        <GraduationCap class="w-5 h-5 text-indigo-600" />
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t.steps[1] }}
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.institutionName }}</label
                            >
                            <input
                                id="institution_name"
                                v-model="form.institution_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.institution_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.institution_name }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.institutionAddress }}</label
                            >
                            <textarea
                                id="institution_address"
                                v-model="form.institution_address"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.courseName }}</label
                            >
                            <input
                                id="course_name"
                                v-model="form.course_name"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.course_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.course_name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.courseDuration }}</label
                            >
                            <input
                                id="course_duration"
                                v-model="form.course_duration"
                                type="text"
                                placeholder="e.g., 4 Years"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p
                                v-if="form.errors.course_duration"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.course_duration }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.roomType }}</label
                            >
                            <select
                                id="room_type"
                                v-model="form.room_type"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="2-seater">
                                    {{ t.twoSeater }}
                                </option>
                                <option value="3-seater">
                                    {{ t.threeSeater }}
                                </option>
                                <option value="4-seater">
                                    {{ t.fourSeater }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.room_type"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.room_type }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.stayDuration }}</label
                            >
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500">{{
                                        t.from
                                    }}</span>
                                    <input
                                        id="stay_duration_from"
                                        v-model="form.stay_duration_from"
                                        type="date"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">{{
                                        t.to
                                    }}</span>
                                    <input
                                        id="stay_duration_to"
                                        v-model="form.stay_duration_to"
                                        type="date"
                                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.contact1 }}</label
                            >
                            <input
                                id="institution_contact1"
                                v-model="form.institution_contact1"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.contact2 }}</label
                            >
                            <input
                                id="institution_contact2"
                                v-model="form.institution_contact2"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Health & Vehicle -->
                <div
                    v-show="currentStep === 3"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                >
                    <div
                        class="flex items-center gap-2 pb-4 border-b border-gray-100"
                    >
                        <Heart class="w-5 h-5 text-indigo-600" />
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t.steps[2] }}
                        </h2>
                    </div>

                    <div class="space-y-5">
                        <!-- Driving License -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >{{ t.drivingLicense }}</label
                            >
                            <div class="flex gap-4">
                                <label
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        v-model="form.has_driving_license"
                                        type="radio"
                                        :value="true"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">{{
                                        t.yes
                                    }}</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        v-model="form.has_driving_license"
                                        type="radio"
                                        :value="false"
                                        class="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-gray-700">{{
                                        t.no
                                    }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Vehicle Details (conditional) -->
                        <div
                            v-if="form.has_driving_license"
                            class="grid grid-cols-1 md:grid-cols-2 gap-5 p-4 bg-gray-50 rounded-xl"
                        >
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.vehicleType }}</label
                                >
                                <select
                                    id="vehicle_type"
                                    v-model="form.vehicle_type"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select</option>
                                    <option value="two_wheeler">
                                        {{ t.twoWheeler }}
                                    </option>
                                    <option value="four_wheeler">
                                        {{ t.fourWheeler }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.vehicle_type"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.vehicle_type }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.vehicleNumber }}</label
                                >
                                <input
                                    id="vehicle_number"
                                    v-model="form.vehicle_number"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p
                                    v-if="form.errors.vehicle_number"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.vehicle_number }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.diseaseHistory }}</label
                            >
                            <textarea
                                id="disease_history"
                                v-model="form.disease_history"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="If any..."
                            ></textarea>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.allergyDetails }}</label
                            >
                            <textarea
                                id="allergy_details"
                                v-model="form.allergy_details"
                                rows="2"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="If any..."
                            ></textarea>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >{{ t.achievements }}</label
                            >
                            <textarea
                                id="special_achievements"
                                v-model="form.special_achievements"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Education, sports, etc."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Guardian Details -->
                <div
                    v-show="currentStep === 4"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                >
                    <div
                        class="flex items-center gap-2 pb-4 border-b border-gray-100"
                    >
                        <Shield class="w-5 h-5 text-indigo-600" />
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t.steps[3] }}
                        </h2>
                    </div>

                    <!-- Guardian 1 -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ t.guardianDetails }} - 1
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianName }}</label
                                >
                                <input
                                    id="guardian1_name"
                                    v-model="form.guardian1_name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianMobile }}</label
                                >
                                <input
                                    id="guardian1_mobile"
                                    v-model="form.guardian1_mobile"
                                    type="tel"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianOccupation }}</label
                                >
                                <input
                                    id="guardian1_occupation"
                                    v-model="form.guardian1_occupation"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianAddress }}</label
                                >
                                <textarea
                                    id="guardian1_address"
                                    v-model="form.guardian1_address"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian 2 -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ t.guardianDetails }} - 2
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianName }}</label
                                >
                                <input
                                    id="guardian2_name"
                                    v-model="form.guardian2_name"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianMobile }}</label
                                >
                                <input
                                    id="guardian2_mobile"
                                    v-model="form.guardian2_mobile"
                                    type="tel"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianOccupation }}</label
                                >
                                <input
                                    id="guardian2_occupation"
                                    v-model="form.guardian2_occupation"
                                    type="text"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="md:col-span-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >{{ t.guardianAddress }}</label
                                >
                                <textarea
                                    id="guardian2_address"
                                    v-model="form.guardian2_address"
                                    rows="2"
                                    class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Photo -->
                    <div class="pt-4 border-t border-gray-100">
                        <div class="text-center">
                            <div
                                class="relative w-24 h-32 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                            >
                                <img
                                    v-if="photoPreviews.guardian_photo"
                                    :src="photoPreviews.guardian_photo"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center h-full text-gray-400"
                                >
                                    <Camera class="w-6 h-6" />
                                </div>
                            </div>
                            <label class="cursor-pointer">
                                <span
                                    class="text-xs text-indigo-600 hover:text-indigo-700"
                                    >{{ t.guardianPhoto }}</span
                                >
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    id="guardian_photo"
                                    @change="
                                        (e) =>
                                            handlePhotoChange(
                                                'guardian_photo',
                                                e,
                                            )
                                    "
                                />
                            </label>
                        </div>
                    </div>

                    <!-- Family Photos -->
                    <div class="pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">
                            {{ t.familyPhotos }}
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div
                                    class="relative w-24 h-32 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                                >
                                    <img
                                        v-if="photoPreviews.family_photo1"
                                        :src="photoPreviews.family_photo1"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center h-full text-gray-400"
                                    >
                                        <Camera class="w-6 h-6" />
                                    </div>
                                </div>
                                <label class="cursor-pointer">
                                    <span
                                        class="text-xs text-indigo-600 hover:text-indigo-700"
                                        >Photo 1</span
                                    >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        id="family_photo1"
                                        @change="
                                            (e) =>
                                                handlePhotoChange(
                                                    'family_photo1',
                                                    e,
                                                )
                                        "
                                    />
                                </label>
                            </div>
                            <div class="text-center">
                                <div
                                    class="relative w-24 h-32 mx-auto mb-2 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50"
                                >
                                    <img
                                        v-if="photoPreviews.family_photo2"
                                        :src="photoPreviews.family_photo2"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center h-full text-gray-400"
                                    >
                                        <Camera class="w-6 h-6" />
                                    </div>
                                </div>
                                <label class="cursor-pointer">
                                    <span
                                        class="text-xs text-indigo-600 hover:text-indigo-700"
                                        >Photo 2</span
                                    >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        id="family_photo2"
                                        @change="
                                            (e) =>
                                                handlePhotoChange(
                                                    'family_photo2',
                                                    e,
                                                )
                                        "
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Payment -->
                <div
                    v-show="currentStep === 5"
                    class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6"
                >
                    <div
                        class="flex items-center gap-2 pb-4 border-b border-gray-100"
                    >
                        <CreditCard class="w-5 h-5 text-indigo-600" />
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t.steps[4] }}
                        </h2>
                    </div>

                    <!-- Fee Display -->
                    <div class="bg-indigo-50 rounded-xl p-6 text-center">
                        <p class="text-sm text-indigo-600 mb-1">
                            {{ t.registrationFee }}
                        </p>
                        <p class="text-3xl font-bold text-indigo-900">
                            ₹{{ registrationFee }}
                        </p>
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-3">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >{{ t.paymentMethod }}</label
                        >

                        <label
                            class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                            :class="
                                form.payment_method === 'razorpay'
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                        >
                            <input
                                v-model="form.payment_method"
                                type="radio"
                                value="razorpay"
                                class="sr-only"
                            />
                            <div
                                class="w-10 h-10 rounded-lg bg-white flex items-center justify-center"
                            >
                                <CreditCard class="w-5 h-5 text-indigo-600" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ t.onlinePayment }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    UPI, Card, Net Banking
                                </p>
                            </div>
                            <div
                                v-if="form.payment_method === 'razorpay'"
                                class="w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center"
                            >
                                <Check class="w-3 h-3 text-white" />
                            </div>
                        </label>

                        <label
                            class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                            :class="
                                form.payment_method === 'cash'
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                        >
                            <input
                                v-model="form.payment_method"
                                type="radio"
                                value="cash"
                                class="sr-only"
                            />
                            <div
                                class="w-10 h-10 rounded-lg bg-white flex items-center justify-center"
                            >
                                <Banknote class="w-5 h-5 text-green-600" />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ t.cashPayment }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Pay at hostel office
                                </p>
                            </div>
                            <div
                                v-if="form.payment_method === 'cash'"
                                class="w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center"
                            >
                                <Check class="w-3 h-3 text-white" />
                            </div>
                        </label>
                    </div>

                    <!-- Declaration -->
                    <div
                        class="p-4 bg-amber-50 rounded-xl border border-amber-100"
                    >
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                required
                                class="mt-1 text-indigo-600 focus:ring-indigo-500 rounded"
                            />
                            <p class="text-sm text-gray-700">
                                {{ t.declaration }}
                            </p>
                        </label>
                    </div>

                    <!-- Payment Error -->
                    <div
                        v-if="paymentError"
                        class="p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2"
                    >
                        <AlertCircle
                            class="w-4 h-4 text-red-500 flex-shrink-0"
                        />
                        <p class="text-sm text-red-600">{{ paymentError }}</p>
                    </div>
                </div>

                <!-- Persistent footer error notice -->
                <button
                    v-if="validationSummary.length"
                    type="button"
                    class="sticky bottom-3 z-10 flex w-full items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-left shadow-lg"
                    @click="scrollToValidationSummary"
                >
                    <span class="flex min-w-0 items-center gap-2">
                        <AlertCircle class="h-5 w-5 shrink-0 text-red-600" />
                        <span class="min-w-0">
                            <span
                                class="block text-sm font-semibold text-red-800"
                            >
                                {{ validationSummary.length }}
                                {{
                                    lang === "hi"
                                        ? "त्रुटियाँ बाकी हैं"
                                        : validationSummary.length === 1
                                          ? "error remains"
                                          : "errors remain"
                                }}
                            </span>
                            <span class="block truncate text-xs text-red-600">
                                {{ validationSummary[0]?.label }}:
                                {{ validationSummary[0]?.message }}
                            </span>
                        </span>
                    </span>
                    <span class="shrink-0 text-xs font-semibold text-red-700">
                        {{ lang === "hi" ? "सभी देखें" : "View all" }}
                    </span>
                </button>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between pt-4">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        @click="prevStep"
                        class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        {{ t.previous }}
                    </button>
                    <div v-else></div>

                    <button
                        v-if="currentStep < totalSteps"
                        type="button"
                        @click="nextStep"
                        class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                        {{ t.next }}
                        <ChevronRight class="w-4 h-4" />
                    </button>

                    <button
                        v-else
                        type="submit"
                        :disabled="isProcessing"
                        class="flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isProcessing">Processing...</span>
                        <span v-else>{{
                            form.payment_method === "razorpay"
                                ? t.payNow
                                : t.submitApplication
                        }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
