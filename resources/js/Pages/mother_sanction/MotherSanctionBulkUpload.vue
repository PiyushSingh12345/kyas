<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Mother Sanction Module</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#">
                  <i class="icon-home"></i>
                </a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <Link :href="route('mother-sanction-list-module')">Mother Sanction List</Link>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">Bulk Upload</a>
              </li>
            </ul>
          </div>

          <!-- Flash Message -->
          <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show`" role="alert">
            <i :class="flashMessage.icon"></i>
            {{ flashMessage.message }}
            <button type="button" class="btn-close" @click="hideFlashMessage" aria-label="Close"></button>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Mother Sanction Bulk Upload</div>
                </div>
                <div class="card-body">
                  <!-- Upload Section -->
                  <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white">
                      <h6 class="mb-0">
                        <i class="fas fa-file-excel me-2"></i>Upload Excel File
                      </h6>
                    </div>
                    <div class="card-body">
                      <p class="text-muted small mb-3">
                        Upload an Excel file with <strong>2 sheets</strong>. <strong>Sheet 1</strong>: all columns shown in preview. <strong>Sheet 2</strong>: lookup by SLS, State and Program Division to add <strong>State Id</strong>, <strong>Mother Sanction Number</strong>, <strong>Full Program division name</strong>, <strong>Status</strong>, <strong>Mother Sanction Date</strong>, <strong>Carry Forward Amount</strong>.
                      </p>
                      <div class="row align-items-end">
                        <div class="col-md-8">
                          <label for="excelFile" class="form-label">Select Excel File</label>
                          <input
                            type="file"
                            class="form-control"
                            id="excelFile"
                            accept=".xlsx,.xls"
                            @change="onFileSelect"
                            ref="fileInput"
                          >
                        </div>
                        <div class="col-md-4">
                          <button
                            type="button"
                            class="btn btn-primary"
                            @click="parseAndPreview"
                            :disabled="!selectedFile || parsing"
                          >
                            <span v-if="parsing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i v-else class="fas fa-upload me-1"></i>
                            {{ parsing ? 'Parsing...' : 'Upload & Preview' }}
                          </button>
                        </div>
                      </div>
                      <div v-if="selectedFile" class="mt-2">
                        <small class="text-muted">Selected: {{ selectedFile.name }}</small>
                      </div>
                    </div>
                  </div>

                  <!-- Preview Section -->
                  <div v-if="previewRows.length > 0" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="mb-0">
                        <i class="fas fa-table me-2"></i>Preview ({{ previewRows.length }} row(s))
                      </h6>
                      <div>
                        <button
                          type="button"
                          class="btn btn-success me-2"
                          @click="confirmAndStore"
                          :disabled="storing"
                        >
                          <span v-if="storing" class="spinner-border spinner-border-sm me-1" role="status"></span>
                          <i v-else class="fas fa-check me-1"></i>
                          {{ storing ? 'Saving...' : 'Confirm & Store' }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" @click="clearPreview">
                          <i class="fas fa-times me-1"></i>Clear Preview
                        </button>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-bordered table-sm table-head-bg-primary preview-table">
                        <thead>
                          <tr>
                            <th v-for="col in previewColumns" :key="col" :class="headerClass(col)">{{ col }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row, index) in previewRows" :key="index">
                            <td v-for="col in previewColumns" :key="col" :class="cellClass(col)">
                              {{ cellDisplay(getCellValue(row, col), col) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div v-else-if="!parsing && !selectedFile" class="text-center text-muted py-5">
                    <i class="fas fa-file-excel fa-4x mb-3"></i>
                    <p class="mb-0">Select an Excel file and click "Upload & Preview" to see the data.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import * as XLSX from 'xlsx'
import axios from 'axios'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const fileInput = ref(null)
const selectedFile = ref(null)
const previewColumns = ref([])
const previewRows = ref([])
const parsing = ref(false)
const storing = ref(false)

// Lookup data for derived columns (State Id, Full Program division name)
const statesList = ref([])
const pdSlsCompList = ref([])
const programDivisionsList = ref([])
const lookupLoaded = ref(false)

const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
})

const showFlash = (type, message, icon = 'fas fa-info-circle') => {
  flashMessage.value = { show: true, type, message, icon }
  setTimeout(() => { flashMessage.value.show = false }, 4000)
}
const hideFlashMessage = () => {
  flashMessage.value.show = false
}

onMounted(async () => {
  try {
    const res = await axios.get('/api/mother-sanction-bulk-upload-lookup')
    if (res.data?.success) {
      statesList.value = res.data.states || []
      pdSlsCompList.value = res.data.pd_sls_comp || []
      programDivisionsList.value = res.data.program_divisions || []
      lookupLoaded.value = true
    }
  } catch (err) {
    showFlash('warning', 'Could not load states/SLS lookup. Derived columns may be empty.', 'fas fa-exclamation-triangle')
  }
})

const onFileSelect = (e) => {
  const file = e.target.files?.[0]
  selectedFile.value = file || null
  previewRows.value = []
  previewColumns.value = []
}

const parseExcelDate = (val) => {
  if (val == null || val === '') return null
  if (typeof val === 'string' && val.trim()) return val
  if (typeof val === 'number' && val > 0) {
    const epoch = new Date(1899, 11, 30).getTime()
    const d = new Date(epoch + val * 86400000)
    return isNaN(d.getTime()) ? val : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
  }
  return String(val)
}

// Normalize string for lookup key
const normalizeKey = (val) => (val != null && val !== '' ? String(val).trim().toLowerCase() : '')

// Find header row in raw sheet data (array of rows)
const findHeaderRow = (json, keywords, maxScan = 10) => {
  for (let r = 0; r < Math.min(json.length, maxScan); r++) {
    const row = json[r] || []
    const cells = (row || []).map((h) => (h != null ? String(h).trim() : ''))
    const nonEmpty = cells.filter(Boolean)
    const hasEnoughColumns = nonEmpty.length >= 4
    const matchCount = nonEmpty.filter((h) => keywords.some((k) => h.toLowerCase().includes(k))).length
    const looksLikeDataHeader = matchCount >= 2
    if (hasEnoughColumns || looksLikeDataHeader) return r
  }
  return 0
}

// Parse one sheet into { headers, rows } with all columns
const parseSheet = (ws, expectedKeywords) => {
  const json = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: false })
  if (!json || json.length < 2) return { headers: [], rows: [] }
  const headerRowIndex = findHeaderRow(json, expectedKeywords)
  const headerRow = json[headerRowIndex] || []
  let maxCols = headerRow.length
  for (let i = headerRowIndex + 1; i < json.length; i++) {
    const r = json[i] || []
    if (r.length > maxCols) maxCols = r.length
  }
  const headers = []
  for (let j = 0; j < maxCols; j++) {
    const h = (headerRow[j] != null ? String(headerRow[j]).trim() : '') || ''
    headers.push(h && h.length > 0 ? h : `Col_${j + 1}`)
  }
  const rows = []
  for (let i = headerRowIndex + 1; i < json.length; i++) {
    const raw = json[i] || []
    const row = {}
    let hasAny = false
    headers.forEach((h, j) => {
      const v = raw[j]
      if (v != null && v !== '') hasAny = true
      row[h] = v != null ? v : ''
    })
    if (hasAny) rows.push(row)
  }
  return { headers, rows }
}

// Get value from row by header name (flexible match)
const getRowVal = (row, headers, ...possibleNames) => {
  const normalized = (s) => (s || '').toLowerCase().replace(/\s+/g, ' ')
  for (const name of possibleNames) {
    const n = normalized(name)
    for (const h of headers) {
      if (h && normalized(h) === n) return row[h] != null ? row[h] : ''
      if (h && normalized(h).includes(n)) return row[h] != null ? row[h] : ''
    }
  }
  return ''
}

const parseAndPreview = () => {
  if (!selectedFile.value) return
  parsing.value = true
  previewRows.value = []
  previewColumns.value = []
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target.result)
      const wb = XLSX.read(data, { type: 'array', cellDates: false })
      const sheetNames = wb.SheetNames || []
      if (sheetNames.length === 0) {
        showFlash('warning', 'Excel file has no sheets.', 'fas fa-exclamation-triangle')
        parsing.value = false
        return
      }

      // --- Sheet 1: all columns in preview ---
      const sheet1Keywords = ['program division', 'budget_head', 'allocation_type', 'allocation_amount', 'mother sanction', 'budget head']
      const ws1 = wb.Sheets[sheetNames[0]]
      const { headers: sheet1Headers, rows: sheet1Rows } = parseSheet(ws1, sheet1Keywords)
      if (sheet1Headers.length === 0 || sheet1Rows.length === 0) {
        showFlash('warning', 'Sheet 1 has no headers or data rows.', 'fas fa-exclamation-triangle')
        parsing.value = false
        return
      }

      // --- Sheet 2: lookup by SLS + State + Program Division → Mother Sanction Number, Status, Mother Sanction Date, Carry Forward Amount, State ---
      const derivedColNames = ['State Id', 'Mother Sanction Number', 'Full Program division name', 'Status', 'Mother Sanction Date', 'Carry Forward Amount']
      let sheet2Lookup = new Map()
      if (sheetNames.length >= 2) {
        const sheet2Keywords = ['sls', 'state', 'program division']
        const ws2 = wb.Sheets[sheetNames[1]]
        const { headers: sheet2Headers, rows: sheet2Rows } = parseSheet(ws2, sheet2Keywords)
        if (sheet2Headers.length > 0 && sheet2Rows.length > 0) {
          for (const row of sheet2Rows) {
            const sls = normalizeKey(getRowVal(row, sheet2Headers, 'sls', 'SLS'))
            const stateVal = normalizeKey(getRowVal(row, sheet2Headers, 'state', 'State'))
            const pd = normalizeKey(getRowVal(row, sheet2Headers, 'program division', 'Program Division'))
            const key = `${sls}|${stateVal}|${pd}`
            const entry = {
              stateName: getRowVal(row, sheet2Headers, 'state', 'State'),
              motherSanctionNumber: getRowVal(row, sheet2Headers, 'mother sanction number', 'Mother Sanction Number'),
              status: getRowVal(row, sheet2Headers, 'status', 'Status'),
              motherSanctionDate: getRowVal(row, sheet2Headers, 'mother sanction date', 'Mother Sanction Date'),
              carryForwardAmount: getRowVal(row, sheet2Headers, 'carry forward amount', 'Carry Forward Amount')
            }
            if (!sheet2Lookup.has(key)) sheet2Lookup.set(key, entry)
          }
        }
      }

      // Resolve State Id from state name (states table)
      const stateNameToId = (name) => {
        if (!name || !statesList.value.length) return ''
        const n = String(name).trim().toLowerCase()
        const found = statesList.value.find((s) => (s.name || '').trim().toLowerCase() === n)
        return found ? String(found.id) : ''
      }

      // Resolve Full Program division name: map Excel SLS to pd_and_sls_comp.full_sls_name → show slsPD; else use Program Division with static replacements or md_program_divisions
      const slsToFullProgramDivisionName = (slsVal, stateId) => {
        if (slsVal != null && slsVal !== '' && pdSlsCompList.value.length > 0) {
          const s = String(slsVal).trim()
          const norm = (v) => (v != null ? String(v).trim().toLowerCase() : '')
          const byState = stateId
            ? pdSlsCompList.value.filter((p) => String(p.state_id) === String(stateId))
            : pdSlsCompList.value
          const match = byState.find((p) => {
            const full = norm(p.full_sls_name)
            return full === norm(s) || full.includes(norm(s)) || norm(p.name) === norm(s) || norm(p.sls_code) === norm(s)
          })
          if (match && match.slsPD != null && String(match.slsPD).trim() !== '') return String(match.slsPD).trim()
          const anyMatch = pdSlsCompList.value.find((p) => {
            const full = norm(p.full_sls_name)
            return full === norm(s) || full.includes(norm(s)) || norm(p.name) === norm(s) || norm(p.sls_code) === norm(s)
          })
          if (anyMatch && anyMatch.slsPD != null && String(anyMatch.slsPD).trim() !== '') return String(anyMatch.slsPD).trim()
        }
        return null
      }
      // Static replacements then md_program_divisions lookup for full form
      const programDivisionDisplay = (pdVal) => {
        if (pdVal == null || pdVal === '') return ''
        const v = String(pdVal).trim()
        const norm = (x) => (x != null ? String(x).trim().toLowerCase() : '')
        const vNorm = norm(v)
        const vUpper = v.toUpperCase()
        if (vUpper === 'SMSP') return 'Sub Mission on Seed and Planting'
        if (vUpper === 'NBM') return 'National Bamboo Mission'
        if (vUpper === 'NMEO-OP') return 'National Mission on Edible Oils-Oil Palm'
        if (vNorm.replace(/-/g, ' ').replace(/\s+/g, ' ') === 'ngepa digital agriculture') return 'Digital Agriculture Mission'
        if (vUpper === 'NMEO-OS') return 'National Mission on Edible Oils-Oil Seeds'
        if (vUpper === 'MIDH') return 'Mission for Integrated Development of Horticulture'
        // Else: get full form from md_program_divisions (match division_name)
        if (programDivisionsList.value.length > 0) {
          const found = programDivisionsList.value.find((pd) => norm(pd.division_name) === vNorm)
          if (found && found.division_name) return String(found.division_name).trim()
        }
        return v
      }

      // Build final rows: Sheet 1 columns + 6 derived columns
      const finalHeaders = [...sheet1Headers, ...derivedColNames]
      const finalRows = sheet1Rows.map((row) => {
        const out = { ...row }
        const sls = getRowVal(row, sheet1Headers, 'sls', 'SLS')
        const pd = getRowVal(row, sheet1Headers, 'program division', 'Program Division')
        const key2 = `${normalizeKey(sls)}|${normalizeKey('')}|${normalizeKey(pd)}`
        let sheet2Match = sheet2Lookup.get(key2)
        if (!sheet2Match && sheet2Lookup.size > 0) {
          for (const [k, v] of sheet2Lookup) {
            const [kSls, kState, kPd] = k.split('|')
            if (kSls === normalizeKey(sls) && kPd === normalizeKey(pd)) {
              sheet2Match = v
              break
            }
          }
        }
        const stateName = sheet2Match?.stateName ?? ''
        const stateId = stateNameToId(stateName)
        out['State Id'] = stateId
        out['Mother Sanction Number'] = sheet2Match?.motherSanctionNumber ?? ''
        const fromMapping = slsToFullProgramDivisionName(sls, stateId || undefined)
        out['Full Program division name'] = fromMapping != null ? fromMapping : programDivisionDisplay(pd)
        out['Status'] = sheet2Match?.status ?? ''
        out['Mother Sanction Date'] = sheet2Match?.motherSanctionDate ?? ''
        out['Carry Forward Amount'] = sheet2Match?.carryForwardAmount ?? ''
        return out
      })

      previewColumns.value = finalHeaders
      previewRows.value = finalRows
      const sheet2Note = sheetNames.length >= 2 && sheet2Lookup.size > 0 ? ' Sheet 2 used for derived columns.' : ''
      showFlash('success', `${finalRows.length} row(s) from Sheet 1 with derived columns.${sheet2Note}`, 'fas fa-check-circle')
    } catch (err) {
      showFlash('danger', err.message || 'Failed to parse Excel file.', 'fas fa-exclamation-triangle')
    } finally {
      parsing.value = false
    }
  }
  reader.onerror = () => {
    showFlash('danger', 'Failed to read file.', 'fas fa-exclamation-triangle')
    parsing.value = false
  }
  reader.readAsArrayBuffer(selectedFile.value)
}

const clearPreview = () => {
  previewColumns.value = []
  previewRows.value = []
  selectedFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

const confirmAndStore = async () => {
  if (!previewRows.value.length) {
    showFlash('warning', 'No preview data to store.', 'fas fa-exclamation-triangle')
    return
  }
  storing.value = true
  try {
    const res = await axios.post('/api/mother-sanction-bulk-insert', { rows: previewRows.value })
    if (res.data?.success) {
      showFlash('success', res.data.message || `${res.data.inserted} record(s) stored.`, 'fas fa-check-circle')
      if (res.data.errors?.length) {
        showFlash('warning', res.data.errors.join(' '), 'fas fa-exclamation-triangle')
      }
    } else {
      showFlash('danger', res.data?.message || 'Store failed.', 'fas fa-exclamation-triangle')
      if (res.data?.errors?.length) showFlash('warning', res.data.errors.join(' '), 'fas fa-exclamation-triangle')
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.message || 'Failed to store.'
    showFlash('danger', msg, 'fas fa-exclamation-triangle')
    if (err.response?.data?.errors?.length) {
      showFlash('warning', err.response.data.errors.join(' '), 'fas fa-exclamation-triangle')
    }
  } finally {
    storing.value = false
  }
}

const getCellValue = (row, col) => {
  const v = row[col]
  if (v != null && v !== '') return v
  const normalized = (col || '').toLowerCase().replace(/\s+/g, ' ')
  for (const key of Object.keys(row)) {
    if (key && key.toLowerCase().replace(/\s+/g, ' ') === normalized) return row[key]
  }
  return row[col] ?? ''
}

const isAmountColumn = (col) => {
  if (!col || typeof col !== 'string') return false
  const c = col.toLowerCase()
  return c.includes('amount') || c.includes('allocation_amount') || c.includes('mother sanction amount') || c.includes('fund released') || c.includes('carry forward amount')
}

const isDateColumn = (col) => {
  if (!col || typeof col !== 'string') return false
  const c = col.toLowerCase()
  return c.includes('date')
}

const isCenterColumn = (col) => {
  if (!col || typeof col !== 'string') return false
  const c = col.toLowerCase()
  return c === 'ron' || c === 'id' || c.includes('sr. no') || c === 'number' || c === 'state id'
}

const headerClass = (col) => {
  if (isAmountColumn(col)) return 'text-end align-middle'
  if (isCenterColumn(col)) return 'text-center align-middle'
  return 'align-middle'
}

const cellClass = (col) => {
  if (isAmountColumn(col)) return 'text-end'
  if (isCenterColumn(col)) return 'text-center'
  const c = (col || '').toLowerCase()
  if (c === 'sls' || c.includes('sls')) return 'sls-cell'
  if (c.includes('budget_head') || c.includes('budget head')) return 'budget-head-cell'
  if (c === 'number') return 'number-cell'
  return ''
}

const cellDisplay = (val, col) => {
  if (val == null || val === '') return '—'
  if (isDateColumn(col)) return parseExcelDate(val)
  if (isAmountColumn(col)) {
    const str = String(val).replace(/,/g, '').replace(/[$₹€£\s]/g, '')
    const n = parseFloat(str)
    return isNaN(n) ? val : n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  }
  return val
}
</script>

<style scoped>
.preview-table {
  width: 100%;
}
.table-head-bg-primary th {
  background-color: #2e7d32 !important;
  color: white !important;
  font-weight: 600;
  font-size: 0.8rem;
  vertical-align: middle;
}
.preview-table td {
  font-size: 0.85rem;
  vertical-align: middle;
}
.preview-table .text-end {
  white-space: nowrap;
}
.preview-table .sls-cell {
  word-wrap: break-word;
  white-space: normal;
  max-width: 320px;
}
.preview-table .budget-head-cell {
  font-family: monospace;
  font-size: 0.8rem;
}
.preview-table .number-cell {
  font-family: monospace;
  font-size: 0.85rem;
}
</style>
