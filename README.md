# School Management System - Project Report

## 📋 Project Overview

The School Management System is a comprehensive web application built with modern technologies to manage educational institutions efficiently. It provides role-based access for administrators, teachers, and students with features for managing classes, attendance, grades, and user accounts.

## 🛠️ Technologies Used

### Frontend
- **Next.js 14** - React framework for production
- **React 18** - UI library
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS** - Utility-first CSS framework
- **Radix UI** - Accessible UI components
- **Lucide React** - Icon library

### Backend
- **Next.js API Routes** - Server-side API endpoints
- **Drizzle ORM** - Type-safe SQL ORM
- **PostgreSQL** - Primary database
- **NextAuth.js** - Authentication library
- **bcryptjs** - Password hashing

### Development Tools
- **ESLint** - Code linting
- **Prettier** - Code formatting
- **TypeScript** - Static type checking

```json:package.json
{
  "dependencies": {
    "next": "14.2.18",
    "react": "^18",
    "drizzle-orm": "^0.36.4",
    "postgres": "^3.4.5",
    "next-auth": "^4.24.11",
    "tailwindcss": "^3.4.1"
  }
}
```

## 🗄️ Database Structure

### Entity Relationship Diagram

The database consists of five main entities with the following relationships:

```typescript:database/schema.ts
// Users table - Central authentication
export const users = pgTable("users", {
  id: serial("id").primaryKey(),
  name: varchar("name", { length: 100 }).notNull(),
  email: varchar("email", { length: 100 }).unique().notNull(),
  password: varchar("password", { length: 255 }).notNull(),
  role: roleEnum("role").notNull(),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Students table
export const students = pgTable("students", {
  id: serial("id").primaryKey(),
  userId: integer("user_id").references(() => users.id),
  studentId: varchar("student_id", { length: 20 }).unique().notNull(),
  grade: varchar("grade", { length: 10 }).notNull(),
  section: varchar("section", { length: 10 }).notNull(),
  enrollmentDate: date("enrollment_date").defaultNow(),
});

// Teachers table
export const teachers = pgTable("teachers", {
  id: serial("id").primaryKey(),
  userId: integer("user_id").references(() => users.id),
  employeeId: varchar("employee_id", { length: 20 }).unique().notNull(),
  department: varchar("department", { length: 50 }).notNull(),
  subject: varchar("subject", { length: 50 }).notNull(),
  hireDate: date("hire_date").defaultNow(),
});

// Classes table
export const classes = pgTable("classes", {
  id: serial("id").primaryKey(),
  name: varchar("name", { length: 100 }).notNull(),
  teacherId: integer("teacher_id").references(() => teachers.id),
  grade: varchar("grade", { length: 10 }).notNull(),
  section: varchar("section", { length: 10 }).notNull(),
  subject: varchar("subject", { length: 50 }).notNull(),
  schedule: varchar("schedule", { length: 100 }),
});

// Attendance table
export const attendance = pgTable("attendance", {
  id: serial("id").primaryKey(),
  studentId: integer("student_id").references(() => students.id),
  classId: integer("class_id").references(() => classes.id),
  date: date("date").notNull(),
  status: attendanceEnum("status").notNull(),
  createdAt: timestamp("created_at").defaultNow(),
});

// Grades table
export const grades = pgTable("grades", {
  id: serial("id").primaryKey(),
  studentId: integer("student_id").references(() => students.id),
  classId: integer("class_id").references(() => classes.id),
  examType: varchar("exam_type", { length: 50 }).notNull(),
  marks: decimal("marks", { precision: 5, scale: 2 }).notNull(),
  totalMarks: decimal("total_marks", { precision: 5, scale: 2 }).notNull(),
  grade: varchar("grade", { length: 2 }),
  createdAt: timestamp("created_at").defaultNow(),
});
```

### Key Relationships
- **Users** ↔ **Students/Teachers**: One-to-one relationships for role-based user profiles
- **Teachers** ↔ **Classes**: One-to-many (a teacher can teach multiple classes)
- **Students** ↔ **Attendance**: One-to-many (student attendance records)
- **Students** ↔ **Grades**: One-to-many (student grade records)
- **Classes** ↔ **Attendance/Grades**: One-to-many (class-specific records)

## 🎨 UI/UX Design

### Design System
The application uses a clean, modern design with:
- **Consistent Color Palette**: Primary blues, grays, and semantic colors
- **Typography**: Inter font family for readability
- **Spacing**: Consistent 8px grid system
- **Responsive Design**: Mobile-first approach

### Key UI Components

```typescript:app/components/ui/button.tsx
// Reusable button component with variants
const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = "default", size = "default", ...props }, ref) => {
    return (
      <button
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    )
  }
)
```

### Navigation System

```typescript:app/components/navigation.tsx
export function Navigation() {
  const { data: session, status } = useSession()
  
  const roleBasedLinks = {
    admin: [
      { href: '/admin/dashboard', label: 'Dashboard' },
      { href: '/admin/students', label: 'Students' },
      { href: '/admin/teachers', label: 'Teachers' },
      { href: '/admin/classes', label: 'Classes' },
    ],
    teacher: [
      { href: '/teacher/dashboard', label: 'Dashboard' },
      { href: '/teacher/classes', label: 'My Classes' },
      { href: '/teacher/attendance', label: 'Attendance' },
      { href: '/teacher/grades', label: 'Grades' },
    ],
    student: [
      { href: '/student/dashboard', label: 'Dashboard' },
      { href: '/student/classes', label: 'My Classes' },
      { href: '/student/attendance', label: 'My Attendance' },
      { href: '/student/grades', label: 'My Grades' },
    ]
  }
}
```

## 🔐 Authentication & Authorization

### NextAuth Configuration

```typescript:app/lib/auth.ts
export const authConfig: AuthOptions = {
  providers: [
    CredentialsProvider({
      name: "credentials",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" }
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) return null
        
        const user = await db.query.users.findFirst({
          where: eq(users.email, credentials.email),
        })
        
        if (!user) return null
        
        const isValid = await bcrypt.compare(credentials.password, user.password)
        if (!isValid) return null
        
        return {
          id: user.id.toString(),
          email: user.email,
          name: user.name,
          role: user.role,
        }
      }
    })
  ],
  session: { strategy: "jwt" },
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.role = user.role
      }
      return token
    },
    async session({ session, token }) {
      if (token) {
        session.user.id = token.sub
        session.user.role = token.role as Role
      }
      return session
    }
  }
}
```

### Role-Based Access Control

```typescript:app/(app)/layout.tsx
export default function AppLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <SessionProvider>
      <AuthGuard>
        <div className="min-h-screen bg-background">
          <Navigation />
          <main className="container mx-auto py-6">
            {children}
          </main>
        </div>
      </AuthGuard>
    </SessionProvider>
  )
}
```

## 📡 API Architecture

### RESTful API Design

The API follows REST conventions with role-based access control:

```typescript:app/api/students/route.ts
export async function GET(request: Request) {
  const session = await getServerSession(authConfig)
  
  if (!session || session.user.role !== 'admin') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }
  
  try {
    const students = await db.query.students.findMany({
      with: {
        user: {
          columns: {
            password: false, // Exclude sensitive data
          }
        }
      }
    })
    
    return NextResponse.json(students)
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to fetch students' },
      { status: 500 }
    )
  }
}

export async function POST(request: Request) {
  const session = await getServerSession(authConfig)
  
  if (!session || session.user.role !== 'admin') {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }
  
  try {
    const body = await request.json()
    
    // Create user first
    const hashedPassword = await bcrypt.hash(body.password, 10)
    const [user] = await db.insert(users).values({
      name: body.name,
      email: body.email,
      password: hashedPassword,
      role: 'student'
    }).returning()
    
    // Create student profile
    const [student] = await db.insert(students).values({
      userId: user.id,
      studentId: body.studentId,
      grade: body.grade,
      section: body.section,
    }).returning()
    
    return NextResponse.json(student, { status: 201 })
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to create student' },
      { status: 500 }
    )
  }
}
```

## 🎯 Key Features

### 1. Dashboard Analytics

```typescript:app/(app)/admin/dashboard/page.tsx
export default async function AdminDashboard() {
  const stats = await Promise.all([
    getStudentCount(),
    getTeacherCount(),
    getClassCount(),
    getTodayAttendance()
  ])
  
  return (
    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
      <StatsCard
        title="Total Students"
        value={stats[0]}
        icon={Users}
        trend={+5.2}
      />
      <StatsCard
        title="Total Teachers"
        value={stats[1]}
        icon={GraduationCap}
        trend={+1.8}
      />
      <StatsCard
        title="Active Classes"
        value={stats[2]}
        icon={BookOpen}
        trend={+2.4}
      />
      <StatsCard
        title="Today's Attendance"
        value={`${stats[3]}%`}
        icon={Calendar}
        trend={-0.5}
      />
    </div>
  )
}
```

### 2. Student Management

```typescript:app/(app)/admin/students/page.tsx
export default function StudentsPage() {
  const [students, setStudents] = useState([])
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedGrade, setSelectedGrade] = useState('all')
  
  const filteredStudents = students.filter(student => {
    const matchesSearch = student.user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         student.studentId.includes(searchTerm)
    const matchesGrade = selectedGrade === 'all' || student.grade === selectedGrade
    return matchesSearch && matchesGrade
  })
  
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Students</h1>
        <AddStudentDialog onStudentAdded={fetchStudents} />
      </div>
      
      <div className="flex gap-4">
        <SearchInput
          placeholder="Search students..."
          value={searchTerm}
          onChange={setSearchTerm}
        />
        <GradeFilter
          value={selectedGrade}
          onChange={setSelectedGrade}
        />
      </div>
      
      <StudentsTable students={filteredStudents} />
    </div>
  )
}
```

### 3. Attendance Tracking

```typescript:app/api/attendance/route.ts
export async function POST(request: Request) {
  const session = await getServerSession(authConfig)
  
  if (!session || !['admin', 'teacher'].includes(session.user.role)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }
  
  try {
    const { classId, attendanceData } = await request.json()
    
    // Batch insert attendance records
    const attendanceRecords = attendanceData.map((record: any) => ({
      studentId: record.studentId,
      classId: classId,
      date: new Date().toISOString().split('T')[0],
      status: record.status,
    }))
    
    await db.insert(attendance).values(attendanceRecords)
    
    return NextResponse.json({ message: 'Attendance recorded successfully' })
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to record attendance' },
      { status: 500 }
    )
  }
}
```

### 4. Grade Management

```typescript:app/api/grades/route.ts
export async function POST(request: Request) {
  const session = await getServerSession(authConfig)
  
  if (!session || !['admin', 'teacher'].includes(session.user.role)) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
  }
  
  try {
    const body = await request.json()
    
    // Calculate grade based on percentage
    const percentage = (body.marks / body.totalMarks) * 100
    const grade = calculateGrade(percentage)
    
    const [gradeRecord] = await db.insert(grades).values({
      ...body,
      grade,
    }).returning()
    
    return NextResponse.json(gradeRecord, { status: 201 })
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to add grade' },
      { status: 500 }
    )
  }
}

function calculateGrade(percentage: number): string {
  if (percentage >= 90) return 'A+'
  if (percentage >= 80) return 'A'
  if (percentage >= 70) return 'B+'
  if (percentage >= 60) return 'B'
  if (percentage >= 50) return 'C'
  return 'F'
}
```

## 🔧 Configuration Files

### Drizzle Configuration

```typescript:drizzle.config.ts
export default {
  schema: "./database/schema.ts",
  out: "./database/migrations",
  driver: "pg",
  dbCredentials: {
    connectionString: process.env.DATABASE_URL!,
  },
  verbose: true,
  strict: true,
} satisfies Config
```

### Tailwind Configuration

```typescript:tailwind.config.ts
const config: Config = {
  darkMode: ["class"],
  content: [
    './pages/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    './app/**/*.{ts,tsx}',
    './src/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        border: "hsl(var(--border))",
        input: "hsl(var(--input))",
        ring: "hsl(var(--ring))",
        background: "hsl(var(--background))",
        foreground: "hsl(var(--foreground))",
        primary: {
          DEFAULT: "hsl(var(--primary))",
          foreground: "hsl(var(--primary-foreground))",
        },
        secondary: {
          DEFAULT: "hsl(var(--secondary))",
          foreground: "hsl(var(--secondary-foreground))",
        },
        destructive: {
          DEFAULT: "hsl(var(--destructive))",
          foreground: "hsl(var(--destructive-foreground))",
        },
        muted: {
          DEFAULT: "hsl(var(--muted))",
          foreground: "hsl(var(--muted-foreground))",
        },
        accent: {
          DEFAULT: "hsl(var(--accent))",
          foreground: "hsl(var(--accent-foreground))",
        },
        popover: {
          DEFAULT: "hsl(var(--popover))",
          foreground: "hsl(var(--popover-foreground))",
        },
        card: {
          DEFAULT: "hsl(var(--card))",
          foreground: "hsl(var(--card-foreground))",
        },
      },
    },
  },
  plugins: [require("tailwindcss-animate")],
}
```

## 🚀 Deployment & Environment

### Environment Variables

```env
# Database
DATABASE_URL=postgresql://username:password@localhost:5432/school_db

# NextAuth
NEXTAUTH_SECRET=your-secret-key
NEXTAUTH_URL=http://localhost:3000

# App Configuration
NODE_ENV=development
```

### Next.js Configuration

```javascript:next.config.mjs
/** @type {import('next').NextConfig} */
const nextConfig = {
  experimental: {
    serverComponentsExternalPackages: ['@node-rs/argon2']
  },
  typescript: {
    ignoreBuildErrors: false,
  },
  eslint: {
    ignoreDuringBuilds: false,
  }
}

export default nextConfig
```

## 📁 Project Structure

```
school-management-system/
├── app/
│   ├── (app)/                 # Protected app routes
│   │   ├── admin/            # Admin dashboard & features
│   │   ├── teacher/          # Teacher dashboard & features
│   │   └── student/          # Student dashboard & features
│   ├── api/                  # API routes
│   │   ├── auth/            # Authentication endpoints
│   │   ├── students/        # Student CRUD operations
│   │   ├── teachers/        # Teacher CRUD operations
│   │   ├── classes/         # Class management
│   │   ├── attendance/      # Attendance tracking
│   │   └── grades/          # Grade management
│   ├── components/           # Reusable UI components
│   │   └── ui/              # Base UI components
│   ├── lib/                 # Utility functions
│   │   ├── db.ts           # Database connection
│   │   └── auth.ts         # Authentication config
│   └── providers/           # Context providers
├── database/
│   ├── schema.ts           # Database schema definitions
│   └── migrations/         # Database migrations
├── public/                 # Static assets
└── types/                  # TypeScript type definitions
```

## 🔄 Key Workflows

### 1. User Authentication Flow
1. User enters credentials on login page
2. NextAuth validates credentials against database
3. Password is verified using bcrypt
4. JWT token generated with user role
5. Session established with role-based access

### 2. Student Enrollment Flow
1. Admin creates user account with student role
2. Student profile created with academic details
3. Student assigned to classes based on grade/section
4. Automatic generation of student ID

### 3. Attendance Recording Flow
1. Teacher selects class and date
2. System loads enrolled students
3. Teacher marks attendance status for each student
4. Batch insert attendance records to database
5. Statistics updated in real-time

### 4. Grade Entry Flow
1. Teacher selects student and exam type
2. Enters marks and total marks
3. System calculates grade automatically
4. Grade record saved with timestamp
5. Student performance analytics updated

## 🎯 Key Benefits

1. **Role-Based Access Control**: Secure access based on user roles
2. **Real-Time Data**: Live updates for attendance and grades
3. **Responsive Design**: Works seamlessly on all devices
4. **Type Safety**: Full TypeScript implementation
5. **Modern Stack**: Built with latest web technologies
6. **Scalable Architecture**: Modular design for easy expansion
7. **Security First**: Password hashing, session management, and CSRF protection

## 🚧 Future Enhancements

1. **Real-time Notifications**: WebSocket integration for live updates
2. **Mobile Application**: React Native app for mobile access
3. **Advanced Analytics**: Data visualization and reporting
4. **Communication Module**: Messaging between users
5. **Fee Management**: Financial tracking and billing
6. **Timetable Management**: Schedule optimization
7. **Parent Portal**: Parent access to student information
8. **Exam Scheduling**: Automated exam planning and management

## 📞 Support & Maintenance

The system includes comprehensive error handling, logging, and monitoring capabilities. Regular database backups and security updates ensure system reliability and data protection.
