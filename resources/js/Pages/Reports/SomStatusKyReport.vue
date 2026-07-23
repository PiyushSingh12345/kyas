<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">MIS Reports &amp; Dashboards</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">SOM Status-KY Report</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                      SOM Status - Krishonnati Yojana (₹ In {{ amountInText }})
                      <span v-if="asOn"> as on {{ asOn }}</span>
                    </span>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-success btn-sm" @click="exportToExcel" :disabled="loading || !!error">
                        <i class="fas fa-file-excel me-1"></i>Excel
                      </button>
                      <button type="button" class="btn btn-secondary btn-sm" @click="exportToCSV" :disabled="loading || !!error">
                        <i class="fas fa-file-csv me-1"></i>CSV
                      </button>
                    </div>
                  </div>
                </div>

                <div class="card-body">
                  <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </div>

                  <div v-else-if="error" class="alert alert-danger">
                    {{ error }}
                  </div>

                  <div v-else>
                    <div class="row mb-4">
                      <div class="col-12">
                        <div class="card border-primary">
                          <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                              <i class="fas fa-filter me-2"></i>Filters
                            </h6>
                          </div>
                          <div class="card-body">
                            <div class="row g-3 align-items-end">
                              <div class="col-md-3">
                                <label for="financialYear" class="form-label fw-bold">Financial Year</label>
                                <select
                                  id="financialYear"
                                  class="form-select"
                                  v-model="selectedFinancialYear"
                                  @change="fetchReportData"
                                >
                                  <option value="2026-27">2026-2027</option>
                                  <option value="2025-26">2025-2026</option>
                                  <option value="2024-25">2024–2025</option>
                                  <option value="2023-24">2023–2024</option>
                                  <option value="2022-23">2022–2023</option>
                                </select>
                              </div>

                              <AmountInFilter v-model="amountIn" col-class="col-md-3" input-id="amountInSelect" />

                              <div class="col-md-3">
                                <button type="button" class="btn btn-outline-secondary" @click="clearFilters">
                                  <i class="fas fa-undo me-1"></i>Reset
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="table-responsive som-table-wrap">
                      <table class="table table-bordered som-status-table mb-0">
                        <thead>
                          <tr>
                            <th class="text-center" style="width: 70px">Sl. No</th>
                            <th>State Name</th>
                            <th class="text-end">PAC Approved Allocation</th>
                            <th class="text-end">1st Mother Sanction</th>
                            <th class="text-end">Expenditure</th>
                            <th class="text-center" style="width: 90px">%</th>
                          </tr>
                        </thead>
                        <tbody>
                          <template v-for="section in sections" :key="section.major_head">
                            <tr class="section-header-row">
                              <td colspan="2" class="fw-bold">{{ section.label }}</td>
                              <td colspan="4"></td>
                            </tr>

                            <tr
                              v-for="row in section.rows"
                              :key="`${section.major_head}-${row.state_id ?? 'agency'}-${row.sl_no}`"
                            >
                              <td class="text-center">{{ row.sl_no }}</td>
                              <td>{{ row.state_name }}</td>
                              <td
                                class="text-end"
                                :class="{ 'agency-pac-cell': row.is_agency }"
                              >
                                {{ formatCell(row.pac_approved) }}
                              </td>
                              <td class="text-end">{{ formatCell(row.mother_sanction) }}</td>
                              <td class="text-end">{{ formatCell(row.expenditure) }}</td>
                              <td class="text-center fw-bold">{{ formatPct(row.pct) }}</td>
                            </tr>

                            <tr class="section-total-row">
                              <td colspan="2" class="fw-bold text-end">Total</td>
                              <td class="text-end fw-bold">{{ formatCell(section.totals.pac_approved) }}</td>
                              <td class="text-end fw-bold">{{ formatCell(section.totals.mother_sanction) }}</td>
                              <td class="text-end fw-bold">{{ formatCell(section.totals.expenditure) }}</td>
                              <td class="text-center fw-bold">{{ formatPct(section.totals.pct) }}</td>
                            </tr>
                          </template>

                          <tr class="grand-total-row">
                            <td colspan="2" class="fw-bold text-end">Grand Total</td>
                            <td class="text-end fw-bold">{{ formatCell(grandTotal.pac_approved) }}</td>
                            <td class="text-end fw-bold">{{ formatCell(grandTotal.mother_sanction) }}</td>
                            <td class="text-end fw-bold">{{ formatCell(grandTotal.expenditure) }}</td>
                            <td class="text-center fw-bold">{{ formatPct(grandTotal.pct) }}</td>
                          </tr>
                        </tbody>
                      </table>
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import * as XLSX from 'xlsx'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import AmountInFilter from '../../Components/Reports/AmountInFilter.vue'
import { useAmountIn } from '../../Composables/useAmountIn'

const loading = ref(true)
const error = ref(null)
const selectedFinancialYear = ref('2026-27')
const sections = ref([])
const grandTotal = ref({
  pac_approved: 0,
  mother_sanction: 0,
  expenditure: 0,
  pct: null,
})
const asOn = ref('')

const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')
const amountFractionDigits = computed(() => (amountIn.value === 'Rupees' ? 2 : 2))

const formatCell = (value) =>
  formatAmount(value ?? 0, { fractionDigits: amountFractionDigits.value })

const formatPct = (value) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return ''
  }
  return `${Math.round(Number(value))}%`
}

const clearFilters = () => {
  selectedFinancialYear.value = '2026-27'
  amountIn.value = 'Lakh'
  fetchReportData()
}

const fetchReportData = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await fetch(
      `/api/som-status-ky-report?financial_year=${encodeURIComponent(selectedFinancialYear.value)}`
    )
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    if (!result.success) throw new Error(result.message || 'Failed to load report')

    sections.value = result.sections || []
    grandTotal.value = result.grand_total || {
      pac_approved: 0,
      mother_sanction: 0,
      expenditure: 0,
      pct: null,
    }
    asOn.value = result.as_on || ''
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load SOM Status-KY report'
    sections.value = []
  } finally {
    loading.value = false
  }
}

const buildExportRows = () => {
  const headers = [
    'Sl. No',
    'State Name',
    'PAC Approved Allocation',
    '1st Mother Sanction',
    'Expenditure',
    '%',
  ]
  const rows = [
    [`SOM Status - Krishonnati Yojana (₹ In ${amountInText.value}) as on ${asOn.value || ''}`],
    [],
    headers,
  ]

  sections.value.forEach((section) => {
    rows.push([section.label, '', '', '', '', ''])
    section.rows.forEach((row) => {
      rows.push([
        row.sl_no,
        row.state_name,
        formatCell(row.pac_approved),
        formatCell(row.mother_sanction),
        formatCell(row.expenditure),
        formatPct(row.pct),
      ])
    })
    rows.push([
      '',
      'Total',
      formatCell(section.totals.pac_approved),
      formatCell(section.totals.mother_sanction),
      formatCell(section.totals.expenditure),
      formatPct(section.totals.pct),
    ])
  })

  rows.push([
    '',
    'Grand Total',
    formatCell(grandTotal.value.pac_approved),
    formatCell(grandTotal.value.mother_sanction),
    formatCell(grandTotal.value.expenditure),
    formatPct(grandTotal.value.pct),
  ])

  return rows
}

const exportToExcel = () => {
  const rows = buildExportRows()
  const worksheet = XLSX.utils.aoa_to_sheet(rows)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'SOM Status KY')
  XLSX.writeFile(workbook, `SOM_Status_KY_Report_${selectedFinancialYear.value}.xlsx`)
}

const exportToCSV = () => {
  const rows = buildExportRows()
  const csv = rows
    .map((row) =>
      row
        .map((cell) => {
          const value = String(cell ?? '')
          return `"${value.replace(/"/g, '""')}"`
        })
        .join(',')
    )
    .join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `SOM_Status_KY_Report_${selectedFinancialYear.value}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

onMounted(fetchReportData)
</script>

<style scoped>
.som-status-table {
  font-size: 0.9rem;
  border-color: #333;
}

.som-status-table th,
.som-status-table td {
  border-color: #333 !important;
  vertical-align: middle;
  padding: 0.4rem 0.55rem;
}

.som-status-table thead th {
  background: #fff;
  font-weight: 700;
  text-align: center;
}

.section-header-row td,
.section-total-row td,
.grand-total-row td {
  background: #f4b183 !important;
  font-weight: 700;
}

.agency-pac-cell {
  background: #ed7d31 !important;
  color: #fff;
  font-weight: 600;
}

.som-table-wrap {
  overflow-x: auto;
}
</style>
