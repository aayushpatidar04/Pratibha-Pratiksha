import os, textwrap, datetime

BASE = "/home/claude/work/laravel-app"
MIG_DIR = "./database/migrations"
MODEL_DIR = "./app/Models"
os.makedirs(MIG_DIR, exist_ok=True)
os.makedirs(MODEL_DIR, exist_ok=True)

# Each table: (table_name, body_lines[list of Schema column strings], model_name, fillable[list], casts{dict}, relations[list of php methods])
tables = []

def enum(col, values, default=None, nullable=False):
    vals = ", ".join(f"'{v}'" for v in values)
    s = f"$table->enum('{col}', [{vals}])"
    if default is not None:
        s += f"->default('{default}')"
    if nullable:
        s += "->nullable()"
    return s + ";"

def fk(col, ref_table="", nullable=False):
    s = f"$table->unsignedBigInteger('{col}')"
    if nullable:
        s += "->nullable()"
    return s + ";"

tables.append(dict(
    name="users",
    up=[
        "$table->id();",
        "$table->string('name');",
        "$table->string('email')->unique();",
        "$table->timestamp('email_verified_at')->nullable();",
        "$table->string('password');",
        "$table->string('avatar')->nullable();",
        "$table->string('phone', 20)->nullable();",
        enum("role", ["super_admin","hostel_admin","warden","accountant","caretaker","staff"], "staff"),
        "$table->boolean('is_active')->default(true);",
        "$table->timestamp('last_sign_in_at')->nullable();",
        "$table->rememberToken();",
        "$table->timestamps();",
    ],
    model="User", extends="Authenticatable", uses=["Illuminate\\Notifications\\Notifiable", "Laravel\\Sanctum\\HasApiTokens"],
    fillable=["name","email","password","avatar","phone","role","is_active","last_sign_in_at"],
    hidden=["password","remember_token"],
    casts={"email_verified_at":"datetime","is_active":"boolean","last_sign_in_at":"datetime","password":"hashed"},
))

tables.append(dict(
    name="buildings",
    up=[
        "$table->id();",
        "$table->string('name', 100);",
        "$table->string('code', 20)->unique();",
        enum("type", ["boys","girls","mixed"]),
        "$table->text('address')->nullable();",
        "$table->integer('total_floors')->default(0);",
        "$table->integer('total_rooms')->default(0);",
        "$table->integer('total_capacity')->default(0);",
        "$table->integer('occupied')->default(0);",
        enum("status", ["active","inactive","maintenance"], "active"),
        "$table->timestamps();",
    ],
    model="Building", fillable=["name","code","type","address","total_floors","total_rooms","total_capacity","occupied","status"],
    casts={}, relations=[
        "public function floors() { return $this->hasMany(Floor::class); }",
        "public function rooms() { return $this->hasMany(Room::class); }",
    ]
))

tables.append(dict(
    name="floors",
    up=[
        "$table->id();",
        "$table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();",
        "$table->integer('floor_number');",
        "$table->string('name', 50);",
        "$table->integer('total_rooms')->default(0);",
        "$table->timestamps();",
    ],
    model="Floor", fillable=["building_id","floor_number","name","total_rooms"],
    casts={}, relations=[
        "public function building() { return $this->belongsTo(Building::class); }",
        "public function rooms() { return $this->hasMany(Room::class); }",
    ]
))

tables.append(dict(
    name="rooms",
    up=[
        "$table->id();",
        "$table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();",
        "$table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();",
        "$table->string('room_number', 20);",
        enum("room_type", ["1_seater","2_seater","3_seater","4_seater","5_seater","other"]),
        "$table->integer('capacity');",
        "$table->integer('occupied_beds')->default(0);",
        "$table->decimal('monthly_rent_per_bed', 10, 2)->default(0.00);",
        "$table->boolean('has_ac')->default(false);",
        "$table->boolean('has_wifi')->default(false);",
        "$table->boolean('has_attached_bath')->default(false);",
        "$table->boolean('has_balcony')->default(false);",
        "$table->boolean('has_study_table')->default(false);",
        enum("status", ["available","occupied","maintenance","partially_occupied"], "available"),
        "$table->timestamps();",
    ],
    model="Room", fillable=["building_id","floor_id","room_number","room_type","capacity","occupied_beds","monthly_rent_per_bed","has_ac","has_wifi","has_attached_bath","has_balcony","has_study_table","status"],
    casts={"has_ac":"boolean","has_wifi":"boolean","has_attached_bath":"boolean","has_balcony":"boolean","has_study_table":"boolean","monthly_rent_per_bed":"decimal:2"},
    relations=[
        "public function building() { return $this->belongsTo(Building::class); }",
        "public function floor() { return $this->belongsTo(Floor::class); }",
        "public function beds() { return $this->hasMany(Bed::class); }",
    ]
))

tables.append(dict(
    name="beds",
    up=[
        "$table->id();",
        "$table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();",
        "$table->string('bed_number', 10);",
        enum("status", ["vacant","occupied","maintenance"], "vacant"),
        "$table->unsignedBigInteger('resident_id')->nullable();",
        "$table->timestamps();",
    ],
    model="Bed", fillable=["room_id","bed_number","status","resident_id"], casts={},
    relations=[
        "public function room() { return $this->belongsTo(Room::class); }",
        "public function resident() { return $this->belongsTo(Resident::class); }",
    ]
))

tables.append(dict(
    name="residents",
    up=[
        "$table->id();",
        "$table->string('resident_code', 30)->unique();",
        "$table->string('first_name', 100);",
        "$table->string('last_name', 100)->nullable();",
        "$table->string('email', 320)->nullable();",
        "$table->string('phone', 20);",
        "$table->string('whatsapp_number', 20)->nullable();",
        "$table->date('date_of_birth')->nullable();",
        enum("gender", ["male","female","other"]),
        "$table->string('blood_group', 10)->nullable();",
        "$table->text('address')->nullable();",
        "$table->string('city', 100)->nullable();",
        "$table->string('state', 100)->nullable();",
        "$table->string('country', 100)->default('India');",
        "$table->string('pincode', 10)->nullable();",
        "$table->string('course', 100)->nullable();",
        "$table->integer('year')->nullable();",
        "$table->string('batch', 50)->nullable();",
        "$table->string('roll_number', 50)->nullable();",
        "$table->string('institute', 200)->nullable();",
        "$table->string('father_name', 100)->nullable();",
        "$table->string('father_phone', 20)->nullable();",
        "$table->string('father_email', 320)->nullable();",
        "$table->string('mother_name', 100)->nullable();",
        "$table->string('mother_phone', 20)->nullable();",
        enum("status", ["active","inactive","suspended","left","upcoming"], "upcoming"),
        "$table->text('photo_url')->nullable();",
        "$table->unsignedBigInteger('created_by')->nullable();",
        "$table->timestamps();",
    ],
    model="Resident", fillable=["resident_code","first_name","last_name","email","phone","whatsapp_number","date_of_birth","gender","blood_group","address","city","state","country","pincode","course","year","batch","roll_number","institute","father_name","father_phone","father_email","mother_name","mother_phone","status","photo_url","created_by"],
    casts={"date_of_birth":"date"},
    relations=[
        "public function stays() { return $this->hasMany(ResidentStay::class); }",
        "public function documents() { return $this->hasMany(Document::class); }",
        "public function vehicles() { return $this->hasMany(Vehicle::class); }",
        "public function invoices() { return $this->hasMany(FeeInvoice::class); }",
        "public function payments() { return $this->hasMany(Payment::class); }",
        "public function complaints() { return $this->hasMany(Complaint::class); }",
        "public function leaves() { return $this->hasMany(Leave::class); }",
        "public function getFullNameAttribute() { return trim($this->first_name.' '.$this->last_name); }",
    ]
))

tables.append(dict(
    name="resident_stays",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();",
        "$table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();",
        "$table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();",
        "$table->foreignId('bed_id')->constrained('beds')->cascadeOnDelete();",
        "$table->date('check_in_date');",
        "$table->date('expected_check_out_date')->nullable();",
        "$table->date('actual_check_out_date')->nullable();",
        "$table->decimal('rent_amount', 10, 2);",
        "$table->decimal('deposit_amount', 10, 2)->default(0.00);",
        enum("bill_type", ["monthly","session"], "monthly"),
        enum("status", ["active","ended","upcoming"], "upcoming"),
        "$table->text('notes')->nullable();",
        "$table->timestamps();",
    ],
    model="ResidentStay", fillable=["resident_id","building_id","floor_id","room_id","bed_id","check_in_date","expected_check_out_date","actual_check_out_date","rent_amount","deposit_amount","bill_type","status","notes"],
    casts={"check_in_date":"date","expected_check_out_date":"date","actual_check_out_date":"date"},
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="documents",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("document_type", ["aadhar_card","pan_card","photo","marksheet","caste_certificate","medical_certificate","parent_consent","other"]),
        "$table->text('file_url');",
        "$table->string('file_name')->nullable();",
        enum("verification_status", ["pending","verified","rejected"], "pending"),
        "$table->text('notes')->nullable();",
        "$table->timestamp('uploaded_at')->useCurrent();",
    ],
    model="Document", fillable=["resident_id","document_type","file_url","file_name","verification_status","notes","uploaded_at"],
    casts={"uploaded_at":"datetime"}, timestamps=False,
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="vehicles",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("vehicle_type", ["two_wheeler","four_wheeler","bicycle","other"]),
        "$table->string('vehicle_number', 30);",
        "$table->string('color', 50)->nullable();",
        "$table->string('model', 100)->nullable();",
        "$table->text('rc_file_url')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="Vehicle", fillable=["resident_id","vehicle_type","vehicle_number","color","model","rc_file_url"],
    casts={}, timestamps=False,
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="fee_invoices",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->foreignId('stay_id')->constrained('resident_stays')->cascadeOnDelete();",
        "$table->string('invoice_number', 50)->unique();",
        enum("fee_type", ["hostel_fee","mess_fee","security_deposit","other"]),
        "$table->decimal('amount', 10, 2);",
        "$table->date('due_date');",
        "$table->decimal('paid_amount', 10, 2)->default(0.00);",
        enum("status", ["pending","paid","partial","overdue","waived"], "pending"),
        "$table->text('description')->nullable();",
        "$table->timestamps();",
    ],
    model="FeeInvoice", fillable=["resident_id","stay_id","invoice_number","fee_type","amount","due_date","paid_amount","status","description"],
    casts={"due_date":"date"},
    relations=[
        "public function resident() { return $this->belongsTo(Resident::class); }",
        "public function payments() { return $this->hasMany(Payment::class, 'invoice_id'); }",
    ]
))

tables.append(dict(
    name="payments",
    up=[
        "$table->id();",
        "$table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->decimal('amount', 10, 2);",
        enum("payment_mode", ["cash","upi","card","bank_transfer","other"]),
        "$table->string('transaction_id', 100)->nullable();",
        "$table->date('payment_date');",
        "$table->text('notes')->nullable();",
        "$table->string('receipt_number', 50)->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="Payment", fillable=["invoice_id","resident_id","amount","payment_mode","transaction_id","payment_date","notes","receipt_number"],
    casts={"payment_date":"date"}, timestamps=False,
    relations=["public function invoice() { return $this->belongsTo(FeeInvoice::class, 'invoice_id'); }"]
))

tables.append(dict(
    name="attendance",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->date('date');",
        enum("attendance_type", ["morning","night","meal"]),
        enum("status", ["present","absent","leave","late"], "present"),
        "$table->unsignedBigInteger('marked_by')->nullable();",
        "$table->text('notes')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="Attendance", fillable=["resident_id","date","attendance_type","status","marked_by","notes"],
    casts={"date":"date"}, timestamps=False,
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="complaints",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->unsignedBigInteger('building_id')->nullable();",
        "$table->unsignedBigInteger('room_id')->nullable();",
        enum("category", ["electrical","plumbing","furniture","wifi","cleaning","security","food","other"]),
        enum("priority", ["low","medium","high","urgent"], "medium"),
        "$table->string('title', 200);",
        "$table->text('description');",
        enum("status", ["open","in_progress","resolved","escalated","rejected"], "open"),
        "$table->unsignedBigInteger('assigned_to')->nullable();",
        "$table->text('resolution_notes')->nullable();",
        "$table->timestamp('resolved_at')->nullable();",
        "$table->integer('rating')->nullable();",
        "$table->timestamps();",
    ],
    model="Complaint", fillable=["resident_id","building_id","room_id","category","priority","title","description","status","assigned_to","resolution_notes","resolved_at","rating"],
    casts={"resolved_at":"datetime"},
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="leaves",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("leave_type", ["home_leave","medical_leave","emergency_leave","day_out","night_pass"]),
        "$table->date('from_date');",
        "$table->date('to_date');",
        "$table->text('reason');",
        "$table->string('destination', 200)->nullable();",
        enum("parent_approval_status", ["pending","approved","rejected"], "pending"),
        enum("admin_approval_status", ["pending","approved","rejected"], "pending"),
        enum("final_status", ["pending","parent_approval_pending","approved","rejected","cancelled","expired"], "pending"),
        "$table->string('gate_pass_code', 50)->nullable();",
        "$table->unsignedBigInteger('approved_by')->nullable();",
        "$table->timestamp('approved_at')->nullable();",
        "$table->timestamps();",
    ],
    model="LeaveRequest", table_override="leaves", fillable=["resident_id","leave_type","from_date","to_date","reason","destination","parent_approval_status","admin_approval_status","final_status","gate_pass_code","approved_by","approved_at"],
    casts={"from_date":"date","to_date":"date","approved_at":"datetime"},
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="emergency_alerts",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("category", ["medical","fire","theft","stuck_in_lift","need_food","disaster","domestic_violence","threat","violence","suicidal","mental_depression","others"]),
        "$table->text('description')->nullable();",
        "$table->string('location', 200)->nullable();",
        enum("status", ["active","resolved","escalated"], "active"),
        "$table->unsignedBigInteger('resolved_by')->nullable();",
        "$table->timestamp('resolved_at')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="EmergencyAlert", fillable=["resident_id","category","description","location","status","resolved_by","resolved_at"],
    casts={"resolved_at":"datetime"}, timestamps=False,
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="mess_menu",
    up=[
        "$table->id();",
        "$table->unsignedBigInteger('building_id')->nullable();",
        "$table->date('menu_date');",
        enum("meal_type", ["breakfast","lunch","snacks","dinner"]),
        "$table->text('items');",
        "$table->text('special_notes')->nullable();",
        "$table->timestamps();",
    ],
    model="MessMenu", fillable=["building_id","menu_date","meal_type","items","special_notes"],
    casts={"menu_date":"date"},
))

tables.append(dict(
    name="inventory",
    up=[
        "$table->id();",
        "$table->string('item_name', 100);",
        enum("category", ["room","student","common"]),
        "$table->integer('total_quantity')->default(0);",
        "$table->integer('in_use')->default(0);",
        "$table->integer('available')->default(0);",
        "$table->integer('damaged')->default(0);",
        "$table->string('unit', 20)->default('pieces');",
        "$table->timestamps();",
    ],
    model="Inventory", fillable=["item_name","category","total_quantity","in_use","available","damaged","unit"],
    casts={},
))

tables.append(dict(
    name="room_inventory",
    up=[
        "$table->id();",
        "$table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();",
        "$table->foreignId('inventory_id')->constrained('inventory')->cascadeOnDelete();",
        "$table->integer('quantity')->default(1);",
        enum("condition", ["good","stained","damaged","missing"], "good"),
        "$table->text('notes')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="RoomInventory", fillable=["room_id","inventory_id","quantity","condition","notes"],
    casts={}, timestamps=False,
    relations=[
        "public function room() { return $this->belongsTo(Room::class); }",
        "public function inventory() { return $this->belongsTo(Inventory::class); }",
    ]
))

tables.append(dict(
    name="gate_passes",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("pass_type", ["day_out","night_pass","visitor_pass","late_entry"]),
        "$table->timestamp('from_time');",
        "$table->timestamp('to_time');",
        "$table->text('purpose')->nullable();",
        "$table->string('visitor_name', 100)->nullable();",
        "$table->string('visitor_phone', 20)->nullable();",
        "$table->string('visitor_id_proof', 100)->nullable();",
        enum("status", ["pending","approved","rejected","cancelled","expired","used"], "pending"),
        "$table->unsignedBigInteger('approved_by')->nullable();",
        "$table->timestamps();",
    ],
    model="GatePass", fillable=["resident_id","pass_type","from_time","to_time","purpose","visitor_name","visitor_phone","visitor_id_proof","status","approved_by"],
    casts={"from_time":"datetime","to_time":"datetime"},
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="disciplinary_actions",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        "$table->date('incident_date');",
        "$table->string('incident_time', 10)->nullable();",
        "$table->text('description');",
        "$table->text('witnesses')->nullable();",
        enum("warning_level", ["verbal","written","final","suspension","expulsion"]),
        "$table->text('action_taken')->nullable();",
        "$table->date('follow_up_date')->nullable();",
        enum("status", ["open","resolved","closed"], "open"),
        "$table->boolean('parent_notified')->default(false);",
        "$table->timestamp('notified_at')->nullable();",
        "$table->unsignedBigInteger('created_by')->nullable();",
        "$table->timestamps();",
    ],
    model="DisciplinaryAction", fillable=["resident_id","incident_date","incident_time","description","witnesses","warning_level","action_taken","follow_up_date","status","parent_notified","notified_at","created_by"],
    casts={"incident_date":"date","follow_up_date":"date","notified_at":"datetime","parent_notified":"boolean"},
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="whatsapp_messages",
    up=[
        "$table->id();",
        enum("recipient_type", ["resident","parent","all_residents","all_parents","staff"]),
        "$table->unsignedBigInteger('recipient_id')->nullable();",
        "$table->string('recipient_phone', 20);",
        enum("message_type", ["text","image","document","template"], "text"),
        "$table->string('template_name', 100)->nullable();",
        "$table->text('content');",
        "$table->text('media_url')->nullable();",
        enum("status", ["sent","delivered","read","failed","scheduled"], "sent"),
        "$table->string('wa_message_id', 100)->nullable();",
        "$table->text('failed_reason')->nullable();",
        "$table->timestamp('scheduled_at')->nullable();",
        "$table->timestamp('sent_at')->useCurrent();",
        "$table->unsignedBigInteger('created_by')->nullable();",
    ],
    model="WhatsappMessage", fillable=["recipient_type","recipient_id","recipient_phone","message_type","template_name","content","media_url","status","wa_message_id","failed_reason","scheduled_at","sent_at","created_by"],
    casts={"scheduled_at":"datetime","sent_at":"datetime"}, timestamps=False,
))

tables.append(dict(
    name="activity_log",
    up=[
        "$table->id();",
        "$table->unsignedBigInteger('user_id')->nullable();",
        "$table->string('entity_type', 50);",
        "$table->unsignedBigInteger('entity_id')->nullable();",
        enum("action", ["created","updated","deleted","viewed","approved","rejected","checked_in","checked_out","paid","sent_message"]),
        "$table->text('description')->nullable();",
        "$table->text('metadata')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="ActivityLog", fillable=["user_id","entity_type","entity_id","action","description","metadata"],
    casts={}, timestamps=False,
))

tables.append(dict(
    name="entry_exit_logs",
    up=[
        "$table->id();",
        "$table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();",
        enum("log_type", ["entry","exit"]),
        enum("method", ["manual","qr","biometric"], "manual"),
        "$table->unsignedBigInteger('verified_by')->nullable();",
        "$table->text('notes')->nullable();",
        "$table->timestamp('created_at')->useCurrent();",
    ],
    model="EntryExitLog", fillable=["resident_id","log_type","method","verified_by","notes"],
    casts={}, timestamps=False,
    relations=["public function resident() { return $this->belongsTo(Resident::class); }"]
))

tables.append(dict(
    name="whatsapp_settings",
    up=[
        "$table->id();",
        "$table->string('instance_token');",
        "$table->string('instance_name', 100);",
        "$table->string('phone_number', 20)->nullable();",
        enum("status", ["connected","disconnected","connecting"], "disconnected"),
        "$table->timestamp('connected_since')->nullable();",
        "$table->timestamp('last_ping')->nullable();",
        "$table->integer('messages_sent_today')->default(0);",
        "$table->integer('failed_count')->default(0);",
        "$table->string('gateway_url', 500);",
        "$table->boolean('is_active')->default(true);",
        "$table->timestamps();",
    ],
    model="WhatsappSetting", fillable=["instance_token","instance_name","phone_number","status","connected_since","last_ping","messages_sent_today","failed_count","gateway_url","is_active"],
    casts={"connected_since":"datetime","last_ping":"datetime","is_active":"boolean"},
))

# ------------- generate migrations -------------
base_time = datetime.datetime(2026, 7, 3, 10, 0, 0)
for i, t in enumerate(tables):
    ts = (base_time + datetime.timedelta(seconds=i)).strftime("%Y_%m_%d_%H%M%S")
    fname = f"{ts}_create_{t['name']}_table.php"
    body = "\n            ".join(t["up"])
    php = f"""<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
        Schema::create('{t['name']}', function (Blueprint $table) {{
            {body}
        }});
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{t['name']}');
    }}
}};
"""
    with open(f"{MIG_DIR}/{fname}", "w") as f:
        f.write(php)

# ------------- generate models -------------
for t in tables:
    model = t["model"]
    table_name = t.get("table_override", t["name"])
    extends = t.get("extends", "Model")
    uses_extra = t.get("uses", [])
    fillable = ",\n        ".join(f"'{c}'" for c in t["fillable"])
    casts = t.get("casts", {})
    casts_lines = ",\n            ".join(f"'{k}' => '{v}'" for k, v in casts.items())
    relations = "\n\n    ".join(t.get("relations", []))
    hidden = t.get("hidden", [])
    hidden_block = ""
    if hidden:
        hidden_list = ",\n        ".join(f"'{h}'" for h in hidden)
        hidden_block = f"""
    protected $hidden = [
        {hidden_list}
    ];
"""
    timestamps_block = ""
    if t.get("timestamps") is False:
        timestamps_block = "\n    public $timestamps = false;\n"

    table_block = ""
    if table_name != _snake_default_table(model) if False else False:
        pass

    use_imports = "\n".join(f"use {u};" for u in uses_extra)
    traits = ["HasFactory"] + [u.split("\\")[-1] for u in uses_extra]
    traits_str = ", ".join(traits)

    table_prop = f"\n    protected $table = '{table_name}';\n" if True else ""

    php = f"""<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
{use_imports}

class {model} extends {extends}
{{
    use {traits_str};
{table_prop}
    protected $fillable = [
        {fillable}
    ];
{hidden_block}{timestamps_block}
    protected function casts(): array
    {{
        return [
            {casts_lines}
        ];
    }}

    {relations}
}}
"""
    with open(f"{MODEL_DIR}/{model}.php", "w") as f:
        f.write(php)

print("Generated", len(tables), "migrations and models")