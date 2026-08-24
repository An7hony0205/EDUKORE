import sys
import re

with open('src/views/StudentProfile.vue', 'r', encoding='utf-8') as f:
    content = f.read()

old_load_tab = re.search(r'const loadTab = async \(tab\) => \{.*?\n  \}', content, re.DOTALL).group(0)

new_load_tab = """const loadTab = async (tab) => {
    activeTab.value = tab
    if (tab === 'academic' && !academicData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/academic`)
        academicData.value = res.data.data
      } catch (error) {
        console.error("Failed to fetch academic history", error)
      }
    } else if (tab === 'finances' && !financesData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/finances`)
        financesData.value = res.data
      } catch (error) {
        console.error("Failed to fetch finances data", error)
      }
    } else if (tab === 'attendance' && !attendanceData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/attendance`)
        attendanceData.value = res.data.data
      } catch (error) {
        console.error("Failed to fetch attendance data", error)
      }
    }
  }"""

content = content.replace(old_load_tab, new_load_tab)

with open('src/views/StudentProfile.vue', 'w', encoding='utf-8') as f:
    f.write(content)
