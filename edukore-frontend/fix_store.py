import sys

with open('src/stores/academic.js', 'r', encoding='utf-8') as f:
    content = f.read()

new_action = """    async createCourse(payload) {
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
    async fetchCourseEnrollments(courseAssignmentId) {"""

content = content.replace("    async fetchCourseEnrollments(courseAssignmentId) {", new_action)

with open('src/stores/academic.js', 'w', encoding='utf-8') as f:
    f.write(content)
