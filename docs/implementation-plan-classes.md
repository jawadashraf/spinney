# Classes & Group Sessions — Filament Blueprint Implementation Plan

Date: 2026-05-14
Scope: Classes, group sessions, bookings, payments, attendance, and public-facing booking

---

## 1. Commands

```bash
# Models & Migrations
php artisan make:model ClassModel --migration --factory --no-interaction
php artisan make:model ClassSession --migration --factory --no-interaction
php artisan make:model ClassBooking --migration --factory --no-interaction
php artisan make:model ClassAttendance --migration --no-interaction
php artisan make:model Attendee --no-interaction

# Enums
php artisan make:enum ClassCategory --no-interaction
php artisan make:enum ClassStatus --no-interaction
php artisan make:enum ClassSessionStatus --no-interaction
php artisan make:enum BookingType --no-interaction
php artisan make:enum BookingStatus --no-interaction
php artisan make:enum PaymentStatus --no-interaction

# Filament Resources
php artisan make:filament-resource ClassModel --view --soft-deletes --no-interaction
php artisan make:filament-resource ClassAttendance --no-interaction

# Filament Relation Managers
php artisan make:filament-relation-manager ClassResource sessions name --no-interaction
php artisan make:filament-relation-manager ClassResource bookings status --no-interaction

# Policies
php artisan make:policy ClassModelPolicy --model=ClassModel --no-interaction
php artisan make:policy ClassBookingPolicy --model=ClassBooking --no-interaction
php artisan make:policy ClassAttendancePolicy --model=ClassAttendance --no-interaction

# Form Requests
php artisan make:request StoreClassBookingRequest --no-interaction
php artisan make:request StoreClassAttendanceRequest --no-interaction

# Notifications
php artisan make:notification ClassBookingConfirmedNotification --no-interaction
php artisan make:notification ClassBookingCancelledNotification --no-interaction
php artisan make:notification ClassSessionReminderNotification --no-interaction
php artisan make:notification ClassAttendanceReminderNotification --no-interaction

# Controller
php artisan make:controller StripeWebhookController --no-interaction

# Install Stripe
composer require stripe/stripe-php
```

---

## 2. Models

### Model: ClassModel

```
Model: ClassModel
  Table: classes
  Attributes:
    - id: bigint, primary
    - team_id: bigint, foreign(teams.id), required
    - title: string, required, max:255
    - slug: string, unique, required, max:255
    - description: text, nullable
    - counselor_id: bigint, foreign(users.id), required
    - counselor_type: string, required                    # CounselorType enum
    - category: string, required                          # ClassCategory enum
    - capacity: integer unsigned, required, default:1
    - price_per_session: decimal(10,2), nullable           # null = free
    - is_free: boolean, default:true
    - venue: string, nullable, max:255
    - status: string, required, default:draft             # ClassStatus enum
    - created_by: bigint, foreign(users.id), nullable
    - created_at: timestamp
    - updated_at: timestamp
    - deleted_at: timestamp, nullable
  Indexes:
    - slug (unique)
    - team_id
    - counselor_id
    - status
    - category
  Relationships:
    - belongsTo: Team via team_id
    - belongsTo: User (counselor) via counselor_id
    - belongsTo: User (creator) via created_by
    - hasMany: ClassSession via class_model_id
    - hasMany: ClassBooking via class_model_id
  Traits:
    - SoftDeletes
    - HasTeam (App\Models\Concerns\HasTeam)
  Casts:
    - counselor_type: App\Enums\CounselorType
    - category: App\Enums\ClassCategory
    - status: App\Enums\ClassStatus
    - is_free: boolean
    - price_per_session: decimal:2
    - deleted_at: datetime
  $attributes:
    - status: 'draft'
    - is_free: true
    - capacity: 1
  Scopes:
    - scopePublished: where status = 'published'
    - scopeUpcoming: where has sessions with date >= today
```

### Model: ClassSession

```
Model: ClassSession
  Table: class_sessions
  Attributes:
    - id: bigint, primary
    - class_model_id: bigint, foreign(classes.id), required, cascadeOnDelete
    - date: date, required
    - start_time: time, required
    - end_time: time, required
    - venue_override: string, nullable, max:255
    - notes: text, nullable
    - status: string, required, default:scheduled      # ClassSessionStatus enum
    - created_at: timestamp
    - updated_at: timestamp
  Indexes:
    - class_model_id + date
    - date + start_time + end_time
    - status
  Relationships:
    - belongsTo: ClassModel via class_model_id
    - hasMany: ClassAttendance via class_session_id
  Casts:
    - date: date
    - start_time: datetime:H:i
    - end_time: datetime:H:i
    - status: App\Enums\ClassSessionStatus
  $attributes:
    - status: 'scheduled'
  Scopes:
    - scopeUpcoming: where date >= today and status = scheduled
    - scopeOnDate($date): where date = $date
  Accessors:
    - getVenueAttribute(): venue_override ?? classModel->venue
    - getAvailableCapacityAttribute(): classModel->capacity - attendances()->where('status', 'booked')->count()
    - getIsFullAttribute(): availableCapacity <= 0
```

### Model: ClassBooking

```
Model: ClassBooking
  Table: class_bookings
  Attributes:
    - id: bigint, primary
    - class_model_id: bigint, foreign(classes.id), required, cascadeOnDelete
    - person_id: bigint, foreign(people.id), required
    - user_id: bigint, foreign(users.id), nullable
    - booking_type: string, required, default:full_class   # BookingType enum
    - status: string, required, default:pending_payment     # BookingStatus enum
    - payment_type: string, required, default:free           # PaymentType enum
    - payment_status: string, required, default:unpaid      # PaymentStatus enum
    - payment_amount: decimal(10,2), nullable
    - stripe_checkout_session_id: string, nullable, unique
    - stripe_payment_intent_id: string, nullable
    - booked_at: timestamp, required
    - confirmed_at: timestamp, nullable
    - cancelled_at: timestamp, nullable
    - cancellation_reason: text, nullable
    - created_by: bigint, foreign(users.id), nullable
    - created_at: timestamp
    - updated_at: timestamp
    - deleted_at: timestamp, nullable
  Indexes:
    - class_model_id
    - person_id
    - user_id
    - status
    - stripe_checkout_session_id (unique)
  Relationships:
    - belongsTo: ClassModel via class_model_id
    - belongsTo: People (person) via person_id
    - belongsTo: User via user_id
    - hasMany: ClassAttendance via class_booking_id
  Traits:
    - SoftDeletes
  Casts:
    - booking_type: App\Enums\BookingType
    - status: App\Enums\BookingStatus
    - payment_type: App\Enums\PaymentType
    - payment_status: App\Enums\PaymentStatus
    - payment_amount: decimal:2
    - booked_at: datetime
    - confirmed_at: datetime
    - cancelled_at: datetime
    - deleted_at: datetime
  $attributes:
    - booking_type: 'full_class'
    - status: 'pending_payment'
    - payment_type: 'free'
    - payment_status: 'unpaid'
  Accessors:
    - isFree(): payment_type === PaymentType::FREE
    - isDropIn(): booking_type === BookingType::DROP_IN
    - isFullClass(): booking_type === BookingType::FULL_CLASS
```

### Model: ClassAttendance

```
Model: ClassAttendance
  Table: class_attendances
  Attributes:
    - id: bigint, primary
    - class_session_id: bigint, foreign(class_sessions.id), required, cascadeOnDelete
    - class_booking_id: bigint, foreign(class_bookings.id), required, cascadeOnDelete
    - person_id: bigint, foreign(people.id), required
    - status: string, required, default:booked         # AttendanceStatus enum
    - marked_by: bigint, foreign(users.id), nullable
    - marked_at: timestamp, nullable
    - notes: text, nullable
    - created_at: timestamp
    - updated_at: timestamp
  Unique constraints:
    - class_session_id + class_booking_id
  Indexes:
    - class_session_id
    - person_id
    - status
  Relationships:
    - belongsTo: ClassSession via class_session_id
    - belongsTo: ClassBooking via class_booking_id
    - belongsTo: People (person) via person_id
    - belongsTo: User (marker) via marked_by
  Casts:
    - status: App\Enums\AttendanceStatus
    - marked_at: datetime
  $attributes:
    - status: 'booked'
```

### Model: Attendee

```
Model: Attendee
  Table: people (uses Parental single-table inheritance)
  Parent: People
  Traits:
    - HasParent (Parental)
  $attributes:
    - type: 'attendee'
  Relationships:
    - hasMany: ClassBooking via person_id
```

### Update: People

```
Update Model: People
  Change: Add 'attendee' => Attendee::class to $childTypes array
  File: App\Models\People
```

---

### Enums

```
Enum: ClassCategory
  Implements: HasLabel, HasColor, HasIcon
  Cases:
    - DRUG_ALCOHOL: label "Drug & Alcohol", color "warning", icon "Heroicon::Beaker"
    - SPIRITUAL: label "Spiritual", color "primary", icon "Heroicon::Sparkles"
    - EDUCATION: label "Education", color "info", icon "Heroicon::AcademicCap"
    - OUTREACH: label "Outreach", color "success", icon "Heroicon::GlobeAlt"
    - WELLBEING: label "Wellbeing", color "success", icon "Heroicon::Heart"

Enum: ClassStatus
  Implements: HasLabel, HasColor
  Cases:
    - DRAFT: label "Draft", color "gray"
    - PUBLISHED: label "Published", color "success"
    - ARCHIVED: label "Archived", color "warning"
    - CANCELLED: label "Cancelled", color "danger"

Enum: ClassSessionStatus
  Implements: HasLabel, HasColor
  Cases:
    - SCHEDULED: label "Scheduled", color "info"
    - CANCELLED: label "Cancelled", color "danger"
    - COMPLETED: label "Completed", color "success"

Enum: BookingType
  Implements: HasLabel, HasColor, HasIcon
  Cases:
    - FULL_CLASS: label "Full Course", color "primary", icon "Heroicon::BookOpen"
    - DROP_IN: label "Drop-in", color "info", icon "Heroicon::CalendarDays"

Enum: BookingStatus
  Implements: HasLabel, HasColor
  Cases:
    - PENDING_PAYMENT: label "Pending Payment", color "warning"
    - CONFIRMED: label "Confirmed", color "success"
    - CANCELLED: label "Cancelled", color "danger"

Enum: PaymentStatus
  Implements: HasLabel, HasColor
  Cases:
    - UNPAID: label "Unpaid", color "warning"
    - PAID: label "Paid", color "success"
    - REFUNDED: label "Refunded", color "info"
    - FAILED: label "Failed", color "danger"
```

---

## 3. Resources

### Resource: ClassResource

```
Resource: ClassResource
  Command: php artisan make:filament-resource ClassModel --view --soft-deletes --no-interaction
  Location: App\Filament\Resources\ClassResource
  Docs: https://filamentphp.com/docs/5.x/panels/resources/overview

  Navigation:
    Group: Appointments
    Icon: Heroicon::AcademicCap
    Sort: 2
    Label: "Classes"
    RecordTitleAttribute: title
    GloballySearchableAttributes: [title, venue]

  Authorization: App\Policies\ClassModelPolicy
    viewAny: user has 'view_any_class_model' permission
    view: user has 'view_class_model' permission
    create: user has 'create_class_model' permission
    update: user has 'update_class_model' permission
    delete: user has 'delete_class_model' permission

  Form:
    Columns: 2

    Section: Class Details
      Component: Filament\Schemas\Components\Section
      Docs: https://filamentphp.com/docs/5.x/schemas/sections
      ColumnSpan: full
      Columns: 2
      Icon: Heroicon::AcademicCap
      Fields:
        Field: title
          Component: Filament\Forms\Components\TextInput
          Docs: https://filamentphp.com/docs/5.x/forms/text-input
          Validation: required, max:255
          Config: ->columnSpan(1)

        Field: slug
          Component: Filament\Forms\Components\TextInput
          Docs: https://filamentphp.com/docs/5.x/forms/text-input
          Validation: required, max:255, scopedUnique:classes,slug
          Config: ->columnSpan(1)

        Field: description
          Component: Filament\Forms\Components\RichEditor
          Docs: https://filamentphp.com/docs/5.x/forms/rich-editor
          Validation: nullable
          Config: ->columnSpanFull()

        Field: category
          Component: Filament\Forms\Components\Select
          Docs: https://filamentphp.com/docs/5.x/forms/select
          Validation: required
          Config: ->options(ClassCategory::class)

        Field: counselor_id
          Component: Filament\Forms\Components\Select
          Docs: https://filamentphp.com/docs/5.x/forms/select
          Validation: required
          Config: ->relationship('counselor', 'name'), ->searchable(), ->preload()

        Field: counselor_type
          Component: Filament\Forms\Components\Select
          Docs: https://filamentphp.com/docs/5.x/forms/select
          Validation: required
          Config: ->options(CounselorType::class)

        Field: capacity
          Component: Filament\Forms\Components\TextInput
          Docs: https://filamentphp.com/docs/5.x/forms/text-input
          Validation: required, integer, min:1
          Config: ->integer(), ->default(1)

        Field: venue
          Component: Filament\Forms\Components\TextInput
          Docs: https://filamentphp.com/docs/5.x/forms/text-input
          Validation: nullable, max:255

        Field: status
          Component: Filament\Forms\Components\Select
          Docs: https://filamentphp.com/docs/5.x/forms/select
          Validation: required
          Config: ->options(ClassStatus::class), ->default(ClassStatus::DRAFT), ->visibleOn('edit')

    Section: Pricing
      Component: Filament\Schemas\Components\Section
      Docs: https://filamentphp.com/docs/5.x/schemas/sections
      ColumnSpan: full
      Columns: 2
      Icon: Heroicon::CurrencyPound
      Fields:
        Field: is_free
          Component: Filament\Forms\Components\Toggle
          Docs: https://filamentphp.com/docs/5.x/forms/toggle
          Validation: required
          Config: ->live(), ->default(true)
          Reactive: when toggled off, show price_per_session; when toggled on, set price_per_session to null

        Field: price_per_session
          Component: Filament\Forms\Components\TextInput
          Docs: https://filamentphp.com/docs/5.x/forms/text-input
          Validation: nullable, numeric, min:0
          Config: ->numeric()->prefix('£')->step(0.01)
          Reactive: visible only when is_free is false; required when is_free is false
          Imports: Filament\Schemas\Components\Utilities\Get

        Field: image
          Component: Filament\Forms\Components\FileUpload
          Docs: https://filamentphp.com/docs/5.x/forms/file-upload
          Validation: nullable, image, max:2048
          Config: ->image()->directory('classes')->visibility('public')->columnSpanFull()

  Imports:
    - Filament\Schemas\Components\Utilities\Get
    - Filament\Schemas\Components\Utilities\Set
    - App\Enums\ClassCategory
    - App\Enums\CounselorType
    - App\Enums\ClassStatus

  Infolist (View Page):
    Columns: 2

    Section: Class Details
      Component: Filament\Infolists\Components\Section
      ColumnSpan: full
      Columns: 2
      Entries:
        Entry: title
          Component: Filament\Infolists\Components\TextEntry
          Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
        Entry: slug
          Component: Filament\Infolists\Components\TextEntry
          Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
          Config: ->copyable()
        Entry: description
          Component: Filament\Infolists\Components\TextEntry
          Docs: https://filamentphp.com/docs/5.x/infolists/text-entry
          Config: ->html()
        Entry: category
          Component: Filament\Infolists\Components\TextEntry
          Config: ->badge()
        Entry: counselor.name
          Component: Filament\Infolists\Components\TextEntry
          Config: ->label('Counselor')
        Entry: counselor_type
          Component: Filament\Infolists\Components\TextEntry
          Config: ->badge()
        Entry: capacity
          Component: Filament\Infolists\Components\TextEntry
        Entry: venue
          Component: Filament\Infolists\Components\TextEntry
        Entry: status
          Component: Filament\Infolists\Components\TextEntry
          Config: ->badge()
        Entry: is_free
          Component: Filament\Infolists\Components\IconEntry
          Config: ->boolean()
        Entry: price_per_session
          Component: Filament\Infolists\Components\TextEntry
          Config: ->money('GBP')
        Entry: created_at
          Component: Filament\Infolists\Components\TextEntry
          Config: ->dateTime()
        Entry: updated_at
          Component: Filament\Infolists\Components\TextEntry
          Config: ->dateTime()

  Table:
    Column: title
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->searchable(), ->sortable()

    Column: counselor.name
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Counselor'), ->searchable()

    Column: category
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge()

    Column: capacity
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->numeric(0), ->sortable()

    Column: is_free
      Component: Filament\Tables\Columns\IconColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/icon
      Config: ->boolean(), ->label('Free'), ->sortable()

    Column: price_per_session
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->money('GBP'), ->sortable()

    Column: status
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->badge()

    Column: sessions_count
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->label('Sessions'), ->counts('sessions'), ->numeric(0), ->sortable()

    Column: created_at
      Component: Filament\Tables\Columns\TextColumn
      Docs: https://filamentphp.com/docs/5.x/tables/columns/text
      Config: ->dateTime(), ->sortable(), ->toggleable(isToggledHiddenByDefault: true)

    Filter: status
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->options(ClassStatus::class)

    Filter: category
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->options(ClassCategory::class)

    Filter: counselor
      Component: Filament\Tables\Filters\SelectFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/select
      Config: ->relationship('counselor', 'name'), ->searchable()

    Filter: trashed
      Component: Filament\Tables\Filters\TrashedFilter
      Docs: https://filamentphp.com/docs/5.x/tables/filters/trashed

    DefaultSort: created_at, desc

  Actions:
    Action: Publish
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row, Edit page header
      Icon: Heroicon::RocketLaunch
      Color: success
      Visibility: only when status is 'draft'
      Authorization: user has 'update_class_model' permission
      Confirmation: "Are you sure you want to publish this class? It will be visible on the public booking page."
      Behavior:
        - Set status to 'published'
        - Validate: title, counselor_id, and at least one session must exist
      Notification: "Class published successfully"

    Action: Archive
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row, Edit page header
      Icon: Heroicon::ArchiveBox
      Color: warning
      Visibility: only when status is 'published'
      Authorization: user has 'update_class_model' permission
      Confirmation: "Are you sure you want to archive this class?"
      Behavior:
        - Set status to 'archived'
      Notification: "Class archived"

    Action: CancelClass
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: table row, Edit page header
      Icon: Heroicon::XCircle
      Color: danger
      Visibility: only when status is 'published' or 'draft'
      Authorization: user has 'update_class_model' permission

      Modal:
        Heading: Cancel Class
        Description: Select whether to also cancel all existing bookings.
        Field: cancel_bookings
          Component: Filament\Forms\Components\Toggle
          Validation: nullable
          Config: ->default(true), ->label('Cancel all existing bookings')

      Behavior:
        - Set class status to 'cancelled'
        - If cancel_bookings is true: cancel all active bookings (set status to 'cancelled', cancelled_at to now)
        - If cancel_bookings is true: cancel all scheduled sessions (set status to 'cancelled')
      Notification: "Class cancelled"
```

### RelationManager: ClassSessionsRelationManager

```
RelationManager: ClassSessionsRelationManager
  Location: App\Filament\Resources\Classes\RelationManagers\SessionsRelationManager
  Command: php artisan make:filament-relation-manager ClassResource sessions name --no-interaction
  Relationship: sessions (hasMany ClassSession)
  Title attribute: date
  Can create: yes
  Can edit: yes
  Can delete: yes

  Form:
    Columns: 1
    Field: date
      Component: Filament\Forms\Components\DatePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: required

    Field: start_time
      Component: Filament\Forms\Components\TimePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: required

    Field: end_time
      Component: Filament\Forms\Components\TimePicker
      Docs: https://filamentphp.com/docs/5.x/forms/date-time-picker
      Validation: required

    Field: venue_override
      Component: Filament\Forms\Components\TextInput
      Docs: https://filamentphp.com/docs/5.x/forms/text-input
      Validation: nullable, max:255

    Field: notes
      Component: Filament\Forms\Components\Textarea
      Docs: https://filamentphp.com/docs/5.x/forms/textarea
      Validation: nullable, max:1000
      Config: ->rows(3)

    Field: status
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->options(ClassSessionStatus::class)

  Table:
    Column: date
      Component: Filament\Tables\Columns\TextColumn
      Config: ->date(), ->sortable()

    Column: start_time
      Component: Filament\Tables\Columns\TextColumn
      Config: ->time(), ->sortable()

    Column: end_time
      Component: Filament\Tables\Columns\TextColumn
      Config: ->time()

    Column: venue
      Component: Filament\Tables\Columns\TextColumn
      Config: ->state(fn (ClassSession $record): ?string => $record->venue_override ?? $record->classModel->venue), ->limit(30)

    Column: available_capacity
      Component: Filament\Tables\Columns\TextColumn
      Config: ->state(fn (ClassSession $record): string => $record->available_capacity . '/' . $record->classModel->capacity), ->label('Capacity')

    Column: status
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()

    Filter: status
      Component: Filament\Tables\Filters\SelectFilter
      Config: ->options(ClassSessionStatus::class)

  Header Actions:
    Action: GenerateRecurringSessions
      Component: Filament\Actions\Action
      Docs: https://filamentphp.com/docs/5.x/actions/overview
      Location: relation manager header
      Icon: Heroicon::CalendarDays
      Color: info

      Modal:
        Heading: Generate Recurring Sessions
        Description: Create sessions repeating on selected days between start and end dates.
        Field: start_date
          Component: Filament\Forms\Components\DatePicker
          Validation: required, after:today
        Field: end_date
          Component: Filament\Forms\Components\DatePicker
          Validation: required, after:start_date
        Field: days_of_week
          Component: Filament\Forms\Components\CheckboxList
          Validation: required, min:1
          Config: ->options(['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'])
        Field: start_time
          Component: Filament\Forms\Components\TimePicker
          Validation: required
          Config: ->default('10:00')
        Field: end_time
          Component: Filament\Forms\Components\TimePicker
          Validation: required
          Config: ->default('12:00')

      Behavior:
        - Validate start_date < end_date
        - For each day_of_week between start_date and end_date, create ClassSession with given times
        - Set venue_override to null (inherit from class)
        - Deduplicate: skip if session already exists for same class_model_id + date + start_time
      Notification: "Generated {count} sessions"
```

### RelationManager: ClassBookingsRelationManager

```
RelationManager: ClassBookingsRelationManager
  Location: App\Filament\Resources\Classes\RelationManagers\BookingsRelationManager
  Command: php artisan make:filament-relation-manager ClassResource bookings status --no-interaction
  Relationship: bookings (hasMany ClassBooking)
  Title attribute: id
  Can create: yes (for admin-booked bookings)
  Can edit: no (status changes via actions)
  Can delete: no (use cancel action)

  Form:
    Columns: 1
    Field: person_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->relationship('person', 'name'), ->searchable(), ->preload()

    Field: booking_type
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->options(BookingType::class), ->default(BookingType::FULL_CLASS)

    Field: payment_type
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->options(PaymentType::class), ->default(PaymentType::FREE)

  Table:
    Column: person.name
      Component: Filament\Tables\Columns\TextColumn
      Config: ->searchable()

    Column: booking_type
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()

    Column: status
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()

    Column: payment_status
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()

    Column: payment_amount
      Component: Filament\Tables\Columns\TextColumn
      Config: ->money('GBP'), ->sortable()

    Column: booked_at
      Component: Filament\Tables\Columns\TextColumn
      Config: ->dateTime(), ->sortable()

    Filter: status
      Component: Filament\Tables\Filters\SelectFilter
      Config: ->options(BookingStatus::class)

    Filter: booking_type
      Component: Filament\Tables\Filters\SelectFilter
      Config: ->options(BookingType::class)

  Actions:
    Action: ConfirmBooking
      Component: Filament\Actions\Action
      Location: table row
      Icon: Heroicon::Check
      Color: success
      Visibility: only when status is 'pending_payment' AND (payment_type is 'free' OR payment_status is 'paid')
      Authorization: user has 'view_any_class_booking' permission
      Behavior:
        - Set status to 'confirmed'
        - Set confirmed_at to now
        - Send ClassBookingConfirmedNotification to person and counselor
      Notification: "Booking confirmed"

    Action: CancelBooking
      Component: Filament\Actions\Action
      Location: table row
      Icon: Heroicon::XCircle
      Color: danger
      Visibility: only when status is not 'cancelled'
      Authorization: user has 'cancel_class_booking' permission

      Modal:
        Heading: Cancel Booking
        Field: cancellation_reason
          Component: Filament\Forms\Components\Textarea
          Validation: nullable, max:500
          Config: ->rows(3)

      Behavior:
        - Set status to 'cancelled'
        - Set cancelled_at to now
        - Set cancellation_reason from form data
        - Set all related ClassAttendance records status to 'cancelled'
        - Send ClassBookingCancelledNotification
      Notification: "Booking cancelled"

    Action: MarkAsRefunded
      Component: Filament\Actions\Action
      Location: table row
      Icon: Heroicon::ArrowUturnLeft
      Color: info
      Visibility: only when payment_status is 'paid'
      Authorization: user has 'mark_refunded_class_booking' permission
      Confirmation: "Confirm this booking has been refunded via Stripe Dashboard?"
      Behavior:
        - Set payment_status to 'refunded'
      Notification: "Booking marked as refunded"
```

### Resource: ClassAttendanceResource

```
Resource: ClassAttendanceResource
  Command: php artisan make:filament-resource ClassAttendance --no-interaction
  Location: App\Filament\Resources\ClassAttendanceResource
  Docs: https://filamentphp.com/docs/5.x/panels/resources/overview

  Navigation:
    Group: Appointments
    Icon: Heroicon::ClipboardDocumentCheck
    Sort: 3
    Label: "Attendance"
    RecordTitleAttribute: id

  Authorization: App\Policies\ClassAttendancePolicy
    viewAny: user has 'view_any_class_attendance' permission
    view: user has 'view_class_attendance' permission
    update: user has 'update_class_attendance' permission

  Form:
    Columns: 1
    Field: class_session_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->relationship('classSession', 'date'), ->searchable(), ->preload()

    Field: person_id
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->relationship('person', 'name'), ->searchable(), ->preload()

    Field: status
      Component: Filament\Forms\Components\Select
      Docs: https://filamentphp.com/docs/5.x/forms/select
      Validation: required
      Config: ->options(AttendanceStatus::class)

    Field: notes
      Component: Filament\Forms\Components\Textarea
      Docs: https://filamentphp.com/docs/5.x/forms/textarea
      Validation: nullable, max:500
      Config: ->rows(3)

  Table:
    Column: classSession.classModel.title
      Component: Filament\Tables\Columns\TextColumn
      Config: ->label('Class'), ->searchable()

    Column: classSession.date
      Component: Filament\Tables\Columns\TextColumn
      Config: ->date(), ->sortable()

    Column: person.name
      Component: Filament\Tables\Columns\TextColumn
      Config: ->searchable()

    Column: status
      Component: Filament\Tables\Columns\TextColumn
      Config: ->badge()

    Column: marker.name
      Component: Filament\Tables\Columns\TextColumn
      Config: ->label('Marked By'), ->sortable()

    Column: marked_at
      Component: Filament\Tables\Columns\TextColumn
      Config: ->dateTime(), ->sortable(), ->toggleable()

    Filter: status
      Component: Filament\Tables\Filters\SelectFilter
      Config: ->options(AttendanceStatus::class)

    Filter: class
      Component: Filament\Tables\Filters\SelectFilter
      Config: ->relationship('classSession.classModel', 'title'), ->searchable()

    Filter: date_range
      Component: Filament\Tables\Filters\Filter
      Form:
        Field: date_from
          Component: Filament\Forms\Components\DatePicker
        Field: date_to
          Component: Filament\Forms\Components\DatePicker
      Config: ->query(fn (Builder $query, array $data): Builder => $query->when($data['date_from'], fn ($q, $date) => $q->whereHas('classSession', fn ($sq) => $sq->whereDate('date', '>=', $date)))->when($data['date_to'], fn ($q, $date) => $q->whereHas('classSession', fn ($sq) => $sq->whereDate('date', '<=', $date))))

    DefaultSort: classSession.date, desc

  Bulk Actions:
    Action: MarkAttended
      Component: Filament\Actions\BulkAction
      Location: bulk
      Icon: Heroicon::Check
      Color: success
      Authorization: user has 'update_class_attendance' permission
      Behavior:
        - Set status to 'attended' for all selected
        - Set marked_by to current user id
        - Set marked_at to now
      Notification: "Selected attendance marked as attended"

    Action: MarkNoShow
      Component: Filament\Actions\BulkAction
      Location: bulk
      Icon: Heroicon::XMark
      Color: danger
      Authorization: user has 'update_class_attendance' permission
      Behavior:
        - Set status to 'no_show' for all selected
        - Set marked_by to current user id
        - Set marked_at to now
      Notification: "Selected attendance marked as no-show"
```

---

## 4. Authorization

```
Resource: ClassResource
  Policy: App\Policies\ClassModelPolicy

  Abilities:
    viewAny: user has 'view_any_class_model' permission
    view: user has 'view_class_model' permission
    create: user has 'create_class_model' permission
    update: user has 'update_class_model' permission
    delete: user has 'delete_class_model' permission

Resource: ClassBooking (via RelationManager)
  Policy: App\Policies\ClassBookingPolicy

  Abilities:
    viewAny: user has 'view_any_class_booking' permission
    view: user has 'view_class_booking' permission
    create: user has 'create_class_booking' permission
    update: user has 'cancel_class_booking' permission (status changes)
    delete: none (use cancel action)

Resource: ClassAttendanceResource
  Policy: App\Policies\ClassAttendancePolicy

  Abilities:
    viewAny: user has 'view_any_class_attendance' permission
    view: user has 'view_class_attendance' permission
    create: user has 'create_class_attendance' permission
    update: user has 'update_class_attendance' permission (marking attendance)
    delete: none (attendance records should not be deleted)

Field Visibility:
  price_per_session: visible only when is_free is false
  status: visible on edit page only, hidden on create
  cancel_bookings toggle: only in CancelClass action modal

Action Authorization:
  PublishClass: user has 'update_class_model' permission
  ArchiveClass: user has 'update_class_model' permission
  CancelClass: user has 'update_class_model' permission
  ConfirmBooking: user has 'view_any_class_booking' permission
  CancelBooking: user has 'cancel_class_booking' permission
  MarkAsRefunded: user has 'mark_refunded_class_booking' permission
  MarkAttended: user has 'update_class_attendance' permission
  MarkNoShow: user has 'update_class_attendance' permission
```

### Shield Permissions & Role Seeding

Update `SimplifiedRolePermissionSeeder` to add:

| Role | class_model | class_booking | class_attendance |
|------|-------------|---------------|------------------|
| counselor | view_any, view | view_any, view (own class only) | view_any, view, update (own class only) |
| manager | view_any, view, create, update | view_any, view, create, cancel, mark_refunded | view_any, view, update |
| admin | all | all | all |
| frontline | view_any, view | view_any, create | view_any, view |
| assessment | view_any | — | — |

---

## 5. Service Architecture

Following Laravel best practices — single-purpose Action classes with dependency injection:

### `App\Services\ClassBookingService`

```php
final class ClassBookingService
{
    public function createBooking(ClassModel $class, People $person, BookingType $type, array $sessionIds, ?User $user): ClassBooking;
    public function cancelBooking(ClassBooking $booking, ?string $reason = null): ClassBooking;
    public function confirmBooking(ClassBooking $booking): ClassBooking;
    public function calculateTotal(ClassModel $class, BookingType $type, array $sessionIds): ?float;
    public function checkCapacity(ClassSession $session): int;
    public function markAttendance(ClassAttendance $attendance, AttendanceStatus $status, User $markedBy): ClassAttendance;
}
```

### `App\Services\StripeCheckoutService`

```php
final class StripeCheckoutService
{
    public function __construct(private ClassBookingService $bookingService) {}

    public function createCheckoutSession(ClassBooking $booking): string;
    public function handleCheckoutCompleted(string $checkoutSessionId): ClassBooking;
    public function markAsRefunded(ClassBooking $booking): ClassBooking;
}
```

### Config — `config/services.php`

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
],
```

### Webhook Route

```
POST /stripe/webhook → App\Http\Controllers\StripeWebhookController
  Handle checkout.session.completed → confirm booking
  Handle charge.refunded → log (manual refund marking in admin)
```

---

## 6. Notifications & Scheduled Commands

```
Notification: ClassBookingConfirmedNotification
  Channels: mail, database
  Queued: yes (implements ShouldQueue)
  To: person (email if available), counselor (mail + database)
  Contains: class title, date(s), counselor name, venue, booking type, payment amount

Notification: ClassBookingCancelledNotification
  Channels: mail, database
  Queued: yes
  To: person (email if available), counselor (mail + database)
  Contains: class title, date(s), cancellation reason

Notification: ClassSessionReminderNotification
  Channels: mail, database
  Queued: yes
  To: person (email if available), counselor (mail + database)
  Contains: class title, session date, start time, venue
  Triggered by: classes:send-reminders command (24h before session)

Notification: ClassAttendanceReminderNotification
  Channels: database only
  Queued: yes
  To: counselor (database only)
  Contains: class title, session date, start time, attendee count
  Triggered by: classes:send-reminders command (1h before session)

Command: classes:send-reminders
  Schedule: daily at 09:00
  Location: routes/console.php
  Config: withoutOverlapping(), onOneServer()
  Logic:
    - Find all ClassSession where date = tomorrow, status = scheduled
    - For each session, find all ClassAttendance where status = 'booked'
    - Send ClassSessionReminderNotification to each person and counselor (24h)
    - Find all ClassSession where date = today, start_time within 1 hour
    - Send ClassAttendanceReminderNotification to counselor (1h)
```

---

## 7. Public-Facing Pages (Livewire Volt)

### Routes

```php
// routes/web.php additions
Route::prefix('classes')->name('classes.')->group(function () {
    Route::get('/', BrowseClasses::class)->name('index');
    Route::get('/{classModel:slug}', ShowClass::class)->name('show');
    Route::get('/{classModel:slug}/book', BookClass::class)->name('book');
});
Route::get('/booking/confirm', BookingConfirm::class)->name('booking.confirm');
Route::get('/booking/cancel', BookingCancel::class)->name('booking.cancel');
Route::get('/my-bookings', MyBookings::class)->middleware('auth')->name('bookings.mine');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
```

### Form Requests

```
Request: StoreClassBookingRequest
  Rules:
    - class_model_id: required, exists:classes,id
    - person_id: required, exists:people,id
    - booking_type: required, in:full_class,drop_in
    - session_ids: required_if:booking_type,drop_in, array
    - session_ids.*: exists:class_sessions,id
    - name: required_if:guest,true, max:255
    - email: required_if:guest,true, email, max:255
    - phone: nullable, string, max:20
    - create_account: boolean
    - password: required_if:create_account,true, min:8, confirmed
```

### Volt Components

**BrowseClasses** — Lists published classes with cards (title, counselor, category, price/free, next session, capacity)
**ShowClass** — Detail page with class info, upcoming sessions table, "Book Now" button
**BookClass** — Two-step form: identify yourself → booking details → payment/free confirmation
**BookingConfirm** — Success page after Stripe redirect
**BookingCancel** — Cancellation page when user cancels Stripe checkout
**MyBookings** — Authenticated user's bookings with status badges, session details, cancel action

---

## 8. Tests

```
ClassResource:
  Authorization:
    - users without 'view_any_class_model' permission cannot access list page
    - users without 'create_class_model' permission cannot create classes
    - users without 'update_class_model' permission cannot edit classes
    - counselors can only view classes where counselor_id matches their user id
    - managers can view all classes
    - admins can perform all actions

  Validation (use dataset pattern):
    - title: required, max:255
    - slug: required, max:255, scopedUnique:classes,slug
    - counselor_id: required, exists:users,id
    - counselor_type: required
    - category: required
    - capacity: required, integer, min:1
    - price_per_session: nullable, numeric, min:0 (required when is_free is false)

  Component Config:
    - is_free toggle hides price_per_session when checked
    - is_free toggle shows price_per_session when unchecked
    - status field only visible on edit page
    - cancel_bookings toggle appears in CancelClass action modal

  Actions:
    - publish action sets status to 'published'
    - publish action fails if no sessions exist
    - archive action sets status to 'archived'
    - cancel class sets status to 'cancelled'
    - cancel class with cancel_bookings=true cancels all bookings and sessions
    - cancel class with cancel_bookings=false only cancels class, not bookings

ClassBooking:
  Authorization:
    - users without 'view_any_class_booking' permission cannot view bookings
    - only managers and admins can cancel bookings

  Validation (use dataset pattern):
    - person_id: required, exists:people,id
    - booking_type: required, in:full_class,drop_in
    - payment_type: required, in:free,paid

  Actions:
    - confirm booking sets status to 'confirmed' and confirmed_at to now
    - cancel booking sets status to 'cancelled', cancelled_at to now
    - cancel booking sets all related attendances to 'cancelled'
    - mark as refunded sets payment_status to 'refunded'

ClassAttendanceResource:
  Authorization:
    - counselors can only mark attendance for sessions in their own classes
    - managers can mark attendance for any class
    - frontline staff can view but not update attendance

  Bulk Actions:
    - mark attended sets status to 'attended', marked_by to current user, marked_at to now
    - mark no-show sets status to 'no_show', marked_by to current user, marked_at to now

ClassBookingService (Unit):
  - createBooking creates ClassBooking + ClassAttendance records
  - createBooking with drop_in only creates attendance for selected sessions
  - createBooking with full_class creates attendance for all upcoming sessions
  - createBooking calculates total: price_per_session x session_count
  - createBooking fails if session capacity exceeded
  - createBooking for free class sets status=confirmed immediately
  - createBooking for paid class sets status=pending_payment
  - checkCapacity returns correct available spots

StripeIntegration (Feature):
  - createCheckoutSession creates Stripe session and stores ID
  - handleCheckoutCompleted updates booking status to confirmed
  - handleCheckoutCompleted updates payment_status to paid
```

---

## 9. Implementation Sprints

| Sprint | Focus | Key Deliverables |
|--------|-------|------------------|
| **1** | Models & Migrations | ClassModel, ClassSession, ClassBooking, ClassAttendance models + migrations; Attendee model; all enums; factories; verify with `php artisan migrate` |
| **2** | Filament Resources | ClassResource (form, table, view, pages); SessionsRelationManager with GenerateRecurringSessions; BookingsRelationManager; ClassAttendanceResource; policies; Shield permissions; seeder update |
| **3** | Service Layer | ClassBookingService; StripeCheckoutService; config/services.php; .env.example entries; StripeWebhookController |
| **4** | Public Pages | Install stripe/stripe-php; Livewire/Volt browse, detail, booking form; Attendee/User creation; free booking flow; route registration |
| **5** | Stripe Integration | Stripe Checkout session creation; webhook handler; BookingConfirm + BookingCancel pages; paid booking flow; MyBookings page |
| **6** | Notifications & Commands | All notification classes; classes:send-reminders command; schedule in console.php; Markdown mail templates |
| **7** | Tests & Polish | Pest tests: booking flow, capacity, attendance, policies, Stripe webhook; Filament resource tests; `php artisan config:clear` before tests; `vendor/bin/pint --dirty --format agent` |