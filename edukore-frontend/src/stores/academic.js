import { defineStore } from 'pinia'
import api from '../api/axios'

export const useAcademicStore = defineStore('academic', {
  state: () => ({
    courses: [],
    enrollments: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchCourses(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/courses', { params })
        this.courses = response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error fetching courses'
        console.error('Failed to fetch courses:', err)
      } finally {
        this.loading = false
      }
    },
    async createCourse(payload) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/courses', payload)
        this.courses.push(response.data)
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error creating course'
        throw err
      } finally {
        this.loading = false
      }
    },
    async updateCourse(id, payload) {
      this.loading = true
      this.error = null
      try {
        const response = await api.patch(`/courses/${id}`, payload)
        const index = this.courses.findIndex(c => c.id === id)
        if (index !== -1) {
          this.courses[index] = response.data
        }
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error updating course'
        throw err
      } finally {
        this.loading = false
      }
    },
    async deleteCourse(id) {
      this.loading = true
      this.error = null
      try {
        await api.delete(`/courses/${id}`)
        const index = this.courses.findIndex(c => c.id === id)
        if (index !== -1) {
          this.courses[index].is_active = false
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Error deleting course'
        throw err
      } finally {
        this.loading = false
      }
    },
    async fetchCourseEnrollments(courseAssignmentId) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/course-assignments/${courseAssignmentId}/enrollments`)
        this.enrollments = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error fetching enrollments'
        throw err
      } finally {
        this.loading = false
      }
    },
    async fetchEvaluationStudents(evaluationId) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get(`/evaluations/${evaluationId}/students`)
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error fetching evaluation students'
        throw err
      } finally {
        this.loading = false
      }
    },
    async saveAttendanceBulk(payload) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/attendance/bulk', payload)
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error saving attendance'
        throw err
      } finally {
        this.loading = false
      }
    },
    async fetchAttendance(courseAssignmentId, date) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/attendance', { params: { course_assignment_id: courseAssignmentId, date } })
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error fetching attendance'
        throw err
      } finally {
        this.loading = false
      }
    },
    async saveGradesBulk(payload) {
      this.loading = true
      this.error = null
      try {
        const response = await api.post('/grades/bulk', payload)
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error saving grades'
        throw err
      } finally {
        this.loading = false
      }
    },
    async fetchGrades(evaluationId) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/grades', { params: { evaluation_id: evaluationId } })
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Error fetching grades'
        throw err
      } finally {
        this.loading = false
      }
    }
  },
})
