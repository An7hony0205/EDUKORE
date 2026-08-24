import sys
with open('src/views/StudentProfile.vue', 'r', encoding='utf-8') as f:
    content = f.read()

old = "console.error(\"Failed to fetch finances data\", error)\n    }\n  }"
new = "console.error(\"Failed to fetch finances data\", error)\n    } else if (tab === 'attendance' && !attendanceData.value) {\n      try {\n        const res = await api.get(`/students/${route.params.id}/attendance`)\n        attendanceData.value = res.data.data\n      } catch (error) {\n        console.error(\"Failed to fetch attendance data\", error)\n      }\n    }\n  }"

content = content.replace(old, new)

with open('src/views/StudentProfile.vue', 'w', encoding='utf-8') as f:
    f.write(content)
