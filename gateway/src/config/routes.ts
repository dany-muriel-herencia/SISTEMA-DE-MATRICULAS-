export const serviceRoutes = {
  auth: process.env.AUTH_SERVICE_URL || 'http://localhost:3001',
  student: process.env.STUDENT_SERVICE_URL || 'http://localhost:3002',
  academic: process.env.ACADEMIC_SERVICE_URL || 'http://localhost:3003',
  schedule: process.env.SCHEDULE_SERVICE_URL || 'http://localhost:3004',
  enrollment: process.env.ENROLLMENT_SERVICE_URL || 'http://localhost:3005',
  finance: process.env.FINANCE_SERVICE_URL || 'http://localhost:3006',
  reporting: process.env.REPORTING_SERVICE_URL || 'http://localhost:3007',
  rectification: process.env.RECTIFICATION_SERVICE_URL || 'http://localhost:3008'
};

