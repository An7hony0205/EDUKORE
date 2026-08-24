# -*- coding: utf-8 -*-
import sys

with open('src/stores/academic.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace fetchCourses to accept params
old_fetch = """    async fetchCourses() {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/courses')
        this.courses = response.data
      } catch (err) {"""

new_fetch = """    async fetchCourses(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await api.get('/courses', { params })
        this.courses = response.data
      } catch (err) {"""

content = content.replace(old_fetch, new_fetch)

# Add updateCourse and deleteCourse actions
old_create = """    async createCourse(payload) {
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
    },"""

new_actions = old_create + """
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
    },"""

content = content.replace(old_create, new_actions)

with open('src/stores/academic.js', 'w', encoding='utf-8') as f:
    f.write(content)
