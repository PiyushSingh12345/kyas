<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Daily Sanction Module</h3>
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
                <Link :href="route('daily-sanction-list')">Daily Sanction List</Link>
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
                  <div class="card-title">Daily Sanction Bulk Upload</div>
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
                        Upload a SPARSH-format Excel file: metadata in rows 1–7 (report title, state, scheme, from/to date, total sanction), table headers in row 8, data from row 9. Columns: S.No. (SLS), SLS Scheme, S. No. (Sanction), IsDBT, Sanction Date, Sanction Status, Object Head, Function Head, Sanction Amount.
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
                            @click="uploadAndPreview"
                            :disabled="!selectedFile || uploading"
                          >
                            <span v-if="uploading" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i v-else class="fas fa-upload me-1"></i>
                            {{ uploading ? 'Uploading...' : 'Upload & Preview' }}
                          </button>
                        </div>
                      </div>
                      <div v-if="selectedFile" class="mt-2">
                        <small class="text-muted">Selected: {{ selectedFile.name }}</small>
                      </div>
                    </div>
                  </div>

                  <!-- Header data (from Excel metadata) -->
                  <div v-if="headerData" class="card border-info mb-4">
                    <div class="card-header bg-info text-white py-2">
                      <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Report metadata</h6>
                    </div>
                    <div class="card-body py-2 small">
                      <div class="row mb-1">
                        <div class="col-12"><strong>Report:</strong> {{ headerData.report_title }}</div>
                      </div>
                      <div class="row">
                        <div class="col-md-4"><strong>Financial Year:</strong> {{ headerData.financial_year || '—' }}</div>
                        <div class="col-md-4"><strong>State:</strong> {{ headerData.state }}</div>
                        <div class="col-md-4"><strong>Scheme (CSS):</strong> {{ headerData.scheme_css || '—' }}</div>
                      </div>
                      <div class="row mt-1">
                        <div class="col-md-4"><strong>Scheme (SLS):</strong> {{ headerData.scheme_sls || '—' }}</div>
                        <div class="col-md-4"><strong>From Date:</strong> {{ headerData.from_date }} <strong>To Date:</strong> {{ headerData.to_date }}</div>
                        <div class="col-md-4"><strong>IsDBT / PaymentMode:</strong> {{ headerData.isdbt_payment_mode || '—' }}</div>
                      </div>
                      <div class="row mt-1">
                        <div class="col-md-4"><strong>Figures In:</strong> {{ headerData.figures_in || '—' }}</div>
                        <div class="col-md-4"><strong>Sanction Amount (Rs.):</strong> {{ headerData.total_sanction || '—' }}</div>
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
                            <th v-for="col in orderedPreviewColumns()" :key="col" :class="headerClass(col)">{{ col }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(row, index) in previewRows" :key="index">
                            <td v-for="col in orderedPreviewColumns()" :key="col" :class="cellClass(col)">
                              {{ cellDisplay(cellValue(row, col), col) }}
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div v-else-if="!uploading && !selectedFile" class="text-center text-muted py-5">
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
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'

const fileInput = ref(null)
const selectedFile = ref(null)
const headerData = ref(null)
const previewColumns = ref([])
const previewRows = ref([])
const uploading = ref(false)
const storing = ref(false)

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

const onFileSelect = (e) => {
  const file = e.target.files?.[0]
  selectedFile.value = file || null
  previewRows.value = []
}

const uploadAndPreview = async () => {
  if (!selectedFile.value) return
  uploading.value = true
  previewRows.value = []
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    const res = await fetch('/api/daily-sanction-bulk-upload-preview', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: formData
    })
    const data = await res.json()
    if (data.success && Array.isArray(data.rows)) {
      headerData.value = data.header_data || null
      previewColumns.value = Array.isArray(data.columns) ? data.columns : []
      previewRows.value = data.rows
      showFlash('success', data.message || `${data.rows.length} row(s) parsed.`, 'fas fa-check-circle')
    } else {
      showFlash('danger', data.message || 'Failed to parse Excel.', 'fas fa-exclamation-triangle')
    }
  } catch (err) {
    showFlash('danger', err.message || 'Upload failed.', 'fas fa-exclamation-triangle')
  } finally {
    uploading.value = false
  }
}

const confirmAndStore = async () => {
  if (previewRows.value.length === 0 || !headerData.value) return
  storing.value = true
  try {
    const res = await fetch('/api/daily-sanction-bulk-store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ header_data: headerData.value, rows: previewRows.value })
    })
    const data = await res.json()
    if (data.success) {
      showFlash('success', data.message || 'Data saved successfully.', 'fas fa-check-circle')
      previewRows.value = []
      selectedFile.value = null
      if (fileInput.value) fileInput.value.value = ''
    } else {
      showFlash('danger', data.message || (data.errors ? JSON.stringify(data.errors) : 'Save failed.'), 'fas fa-exclamation-triangle')
    }
  } catch (err) {
    showFlash('danger', err.message || 'Save failed.', 'fas fa-exclamation-triangle')
  } finally {
    storing.value = false
  }
}

const clearPreview = () => {
  headerData.value = null
  previewColumns.value = []
  previewRows.value = []
  selectedFile.value = null
  if (fileInput.value) fileInput.value.value = ''
}

/* Preferred column order to match SPARSH report preview (screenshot). Hide generic Column_* and Grand Total headers. */
const PREFERRED_COLUMN_ORDER = [
  'S.No. (SLS)',
  'SLS Scheme',
  'SLS Name',
  'S. No. (Sanction)',
  'Daily Sanction Number',
  'IsDBT',
  'Sanction Date',
  'Sanction Status',
  'Object Head',
  'Function Head',
  'Sanction Amount',
  'Financial Year',
  'State Id',
  'Mother Sanction No.',
  'IFd No',
  'Mother Sanction Amount'
]
const HIDDEN_PREVIEW_COLUMNS = ['Available Amount']

const isGenericOrTotalHeader = (col) => {
  if (!col || typeof col !== 'string') return true
  const c = col.trim().toLowerCase()
  return c.startsWith('column_') || c.includes('grand total') || c === 'total (sanction)'
}

const normalizeCol = (s) => (s || '').trim().toLowerCase().replace(/\s+/g, ' ')

const orderedPreviewColumns = () => {
  const cols = previewColumns.value || []
  if (cols.length === 0) return cols
  const hidden = HIDDEN_PREVIEW_COLUMNS.map((c) => normalizeCol(c))
  const ordered = []
  for (const preferred of PREFERRED_COLUMN_ORDER) {
    const match = cols.find((c) => normalizeCol(c) === normalizeCol(preferred))
    if (match) ordered.push(match)
  }
  for (const col of cols) {
    if (!ordered.includes(col) && !hidden.includes(normalizeCol(col))) ordered.push(col)
  }
  return ordered.length ? ordered : cols
}

const isAmountColumn = (col) => {
  if (!col || typeof col !== 'string') return false
  const c = col.toLowerCase()
  return c.includes('amount') || c.includes('sanction amount') || c.includes('mother sanction amount') || c.includes('available amount')
}

const isCenterColumn = (col) => {
  const c = (col || '').toLowerCase()
  return c.includes('s.no') || c.includes('object head') || c.includes('function head') ||
    c.includes('isdbt') || c.includes('sanction status')
}

const isSlsSchemeColumn = (col) => {
  const c = (col || '').toLowerCase()
  return c.includes('sls scheme')
}

const isSlsNameColumn = (col) => {
  const c = (col || '').toLowerCase()
  return c.includes('sls name') && !c.includes('sls scheme')
}

const headerClass = (col) => {
  if (isAmountColumn(col)) return 'text-end align-middle'
  if (isCenterColumn(col)) return 'text-center align-middle'
  return 'align-middle'
}

const cellClass = (col) => {
  if (isAmountColumn(col)) return 'text-end'
  if (isSlsSchemeColumn(col) || isSlsNameColumn(col)) return 'sls-scheme-cell'
  if (isCenterColumn(col)) return 'text-center'
  return ''
}

/* Get cell value: exact key first, then match by normalized column name so all data in a row shows even if keys differ (e.g. newlines) */
const cellValue = (row, col) => {
  if (row[col] != null && row[col] !== '') return row[col]
  const want = normalizeCol(col)
  if (!want) return null
  for (const key of Object.keys(row)) {
    if (normalizeCol(key) === want) return row[key]
  }
  const cols = orderedPreviewColumns()
  const idx = cols.indexOf(col)
  if (idx >= 0 && row[cols[idx]] != null && row[cols[idx]] !== '') return row[cols[idx]]
  return row[col] ?? null
}

const cellDisplay = (val, col) => {
  if (val == null || val === '') return '—'
  if (isAmountColumn(col)) {
    const n = parseFloat(String(val).replace(/,/g, ''))
    return isNaN(n) ? val : n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  }
  return val
}

const formatNum = (v) => {
  if (v == null || v === '') return '-'
  const n = parseFloat(v)
  return isNaN(n) ? v : n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.preview-table {
  width: 100%;
}
.table-head-bg-primary th {
  background-color: #007bff !important;
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
.preview-table .sls-scheme-cell {
  word-wrap: break-word;
  white-space: normal;
  max-width: 220px;
}
</style>
