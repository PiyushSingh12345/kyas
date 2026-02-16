<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Reports</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator"><i class="icon-arrow-right"></i></li>
              <li class="nav-item">
                <a href="#">Budget Phase Report</a>
              </li>
            </ul>
          </div>

          <!-- Success/Error Messages -->
          <div v-if="message" :class="`alert alert-${messageType} alert-dismissible fade show`" role="alert">
            {{ message }}
            <button type="button" class="btn-close" @click="clearMessage"></button>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Budget Phase Report</div>
                </div>

                <div class="card-body">
                  <!-- Filters Section -->
                  <div class="row mb-4">
                    <div class="col-12">
                      <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                          <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filters
                          </h6>
                        </div>
                        <div class="card-body">
                          <div class="row g-3">
                              <!-- Financial Year Filter -->
                              <div class="col-md-4">
                                <label for="financialYear" class="form-label fw-bold">Financial Year</label>
                                <select class="form-select" id="financialYear" v-model="financialYear" @change="onFinancialYearChange">
                          <option value="2025-26">2025-26</option>
                          <option value="2024-25">2024-25</option>
                                  <option value="2023-24">2023-24</option>
                                  <option value="2022-23">2022-23</option>
                        </select>
                    </div>

                            <!-- Budget Phase Filter -->
                            <div class="col-md-4">
                              <label for="budgetPhase" class="form-label fw-bold">Budget Phase</label>
                          <select class="form-select" id="budgetPhase" v-model="selectedPhase" @change="fetchBudgetHeads">
                            <option disabled value="0">Select Budget Phase</option>
                            <option value="BE">BE</option>
                            <option value="RE">RE</option>
                            <option value="FE">FE</option>
                          </select>
                            </div>

                            <!-- Search Filter -->
                            <div class="col-md-4">
                              <label for="searchTerm" class="form-label fw-bold">Search Budget Head</label>
                              <input 
                                type="text" 
                                id="searchTerm" 
                                class="form-control" 
                                v-model="searchTerm"
                                placeholder="Search by code or description..."
                              >
                            </div>
                          </div>

                          <!-- Filter Actions -->
                          <div class="row mt-3">
                            <div class="col-12">
                              <button 
                                class="btn btn-secondary btn-sm me-2" 
                                @click="clearFilters"
                          >
                                <i class="fas fa-times me-1"></i>Clear Filters
                              </button>
                              <span class="text-muted ms-2">
                                Showing {{ computedFilteredBudgetHeads.length }} budget heads
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Export Buttons -->
                  <div class="row mb-3" v-if="filteredBudgetHeads.length > 0">
                    <div class="col-12 d-flex justify-content-end align-items-center">
                      <div class="export-buttons">
                        <button 
                          class="btn btn-success btn-sm me-2" 
                          @click="exportToExcel"
                          title="Export to Excel"
                        >
                          <i class="fas fa-file-excel me-1"></i>EXCEL
                        </button>
                        <button 
                          class="btn btn-info btn-sm me-2" 
                          @click="exportToCSV"
                          title="Export to CSV"
                        >
                          <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                        <button 
                          class="btn btn-danger btn-sm" 
                          @click="exportToPDF"
                          title="Export to PDF"
                        >
                          <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- No data message -->
                  <div v-if="selectedPhase !== '0' && computedFilteredBudgetHeads.length === 0" class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>No budget data found</strong> for the selected Financial Year ({{ financialYear }}) and Budget Phase ({{ selectedPhase }}).
                    <br>Please ensure that budget heads are available for this combination.
                  </div>

                  <div v-if="computedFilteredBudgetHeads.length !== 0" class="table-responsive mt-3" id="reportTable">
                    <div class="d-flex justify-content-between align-items-center mb-2 float-end">
                      <div class="alert alert-info py-2 px-3 mb-0">
                        <strong>(₹ In Lakhs)</strong> 
                      </div>
                    </div>
                    
                    <table class="table table-bordered table-head-bg-primary">
                      <thead>
                        <tr>
                          <th>Budget Head</th>
                          <th>Head Description</th>
                          <th>Budget Amount <small class="text-capitalize">(₹ In Lakhs)</small></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in computedFilteredBudgetHeads" :key="item.id">
                          <td>
                            <input
                              type="text"
                              v-model="item.budget"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <input
                              type="text"
                              v-model="item.description"
                              class="form-control tableform-control-withoutbg"
                              disabled
                            />
                          </td>
                          <td>
                            <div class="d-flex align-items-center">
                              <!-- <input
                                v-if="item.draft_flag === 0"
                                type="number"
                                v-model="item.amount"
                                class="form-control tableform-control-withoutbg"
                                placeholder="Enter amount"
                              /> -->
                              <input
                                type="number"
                                :value="item.amount??0"
                                class="form-control tableform-control-withoutbg fw-bold text-success"
                                readonly
                              />
                              <!-- <span v-if="item.amount && item.amount > 0" class="ms-2 badge bg-success">
                                <i class="fas fa-check"></i>
                              </span>
                              <span v-else class="ms-2 badge bg-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                              </span> -->
                            </div>
                          </td>
                        </tr>
                      </tbody>
                      <tfoot class="table-dark">
                        <tr>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div> <!-- end of card-body -->

                <!-- <div class="card-footer" v-if="filteredBudgetHeads.length !== 0">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button 
                        class="btn btn-primary me-1" 
                        @click="saveAsDraft" 
                        :disabled="isSubmitted || isProcessing"
                        :class="{ 'opacity-50': isProcessing }"
                      >
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Save as Draft
                      </button>
                      <button 
                        class="btn btn-success me-1" 
                        @click="submit" 
                        :disabled="isSubmitted || isProcessing"
                        :class="{ 'opacity-50': isProcessing }"
                      >
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Submit
                      </button>
                      <button 
                        class="btn btn-danger me-1" 
                        @click="reset" 
                        :disabled="isSubmitted || isProcessing"
                      >
                        Reset
                      </button>
                    </div>
                  </div>
                </div>  -->
                <!-- end of card-footer -->

              </div> <!-- end of card -->
            </div>
          </div>
        </div>
      </div>
      <Footer />
    </div>
  </div>
</template>

<script>
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import { ref, reactive, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import * as XLSX from 'xlsx'

export default {
  name: 'BudgetPhase',
  components: {
    Header,
    Sidebar,
    Footer
  },
  setup() {
    const selectedPhase = ref('0')
    const financialYear = ref('2025-26')
    const filteredBudgetHeads = ref([])
    const searchTerm = ref('')
    const isSubmitted = ref(false)
    const isProcessing = ref(false)
    const message = ref('')
    const messageType = ref('success')

    const clearMessage = () => {
      message.value = ''
      messageType.value = 'success'
    }

    const showMessage = (msg, type = 'success') => {
      message.value = msg
      messageType.value = type
      setTimeout(() => {
        clearMessage()
      }, 5000)
    }

    const fetchBudgetHeads = async () => {
      if (selectedPhase.value === '0') {
        filteredBudgetHeads.value = []
        return
      }

      try {
        const response = await fetch(`/api/budget-heads?phase=${selectedPhase.value}&year=${financialYear.value}`)
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        filteredBudgetHeads.value = data

        // If ALL draft_flag === 1, disable buttons
        isSubmitted.value = data.every(item => item.draft_flag === 1)
      } catch (error) {
        console.error('Error fetching budget heads:', error)
        showMessage('Error fetching budget heads. Please try again.', 'danger')
      }
    }

    // Function to handle financial year change
    const onFinancialYearChange = async () => {
      // Clear search when financial year changes
      searchTerm.value = ''
      // Fetch data for the new financial year if phase is selected
      if (selectedPhase.value !== '0') {
        await fetchBudgetHeads()
      }
    }

    const saveAsDraft = async () => {
      if (isProcessing.value) return
      
      isProcessing.value = true
      
      try {
        const allocations = filteredBudgetHeads.value
          .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
          .map(item => ({
            financial_year: financialYear.value,
            budget_phase: selectedPhase.value,
            budget_head_id: item.id,
            budget_amount: item.amount,
            status: 1,
            draft_flag: 0
          }))

        if (allocations.length === 0) {
          showMessage('Please enter at least one budget amount before saving.', 'warning')
          isProcessing.value = false
          return
        }

        const response = await fetch('/budget-phase', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ allocations })
        })

        const result = await response.json()

        if (response.ok) {
          showMessage('Draft saved successfully!', 'success')
          isSubmitted.value = true
          // Refresh the data
          await fetchBudgetHeads()
        } else {
          throw new Error(result.message || 'Failed to save draft')
        }
      } catch (error) {
        console.error("Error saving draft:", error)
        showMessage(error.message || 'Error saving draft. Please try again.', 'danger')
      } finally {
        isProcessing.value = false
      }
    }

    const submit = async () => {
      if (isProcessing.value) return
      
      // Show confirmation dialog
      if (!confirm('Are you sure you want to submit? This action cannot be undone.')) {
        return
      }

      isProcessing.value = true
      
      try {
        const allocations = filteredBudgetHeads.value
          .filter(item => item.draft_flag === 0 && item.amount !== null && item.amount !== '' && item.amount !== undefined)
          .map(item => ({
            financial_year: financialYear.value,
            budget_phase: selectedPhase.value,
            budget_head_id: item.id,
            budget_amount: item.amount,
            status: 1,
            draft_flag: 1
          }))

        if (allocations.length === 0) {
          showMessage('Please enter at least one budget amount before submitting.', 'warning')
          isProcessing.value = false
          return
        }

        const response = await fetch('/budget-phase', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ allocations })
        })

        const result = await response.json()

        if (response.ok) {
          showMessage('Budget submitted successfully!', 'success')
          isSubmitted.value = true
          // Refresh the data
          await fetchBudgetHeads()
        } else {
          throw new Error(result.message || 'Failed to submit budget')
        }
      } catch (error) {
        console.error("Error submitting budget:", error)
        showMessage(error.message || 'Error submitting budget. Please try again.', 'danger')
      } finally {
        isProcessing.value = false
      }
    }

    const reset = () => {
      filteredBudgetHeads.value.forEach(item => {
        if (item.draft_flag === 0) {
          item.amount = null
        }
      })
      showMessage('Form has been reset.', 'info')
    }

    // Computed property for total budget amount
    const totalBudgetAmount = computed(() => {
      const total = filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
      return total
    })

    // Computed property for allocated amount (amounts that are not null/empty)
    const allocatedAmount = computed(() => {
      const total = filteredBudgetHeads.value.reduce((total, item) => {
        return total + (parseFloat(item.amount) || 0)
      }, 0)
      return total
    })

    // Computed property for remaining amount (budget heads with no amount)
    const remainingAmount = computed(() => {
      const budgetHeadsWithAmount = filteredBudgetHeads.value.filter(item => 
        item.amount !== null && item.amount !== '' && item.amount !== undefined
      ).length
      const totalBudgetHeads = filteredBudgetHeads.value.length
      return totalBudgetHeads - budgetHeadsWithAmount
    })

    // Function to format numbers in Indian numbering format
    const formatIndianNumber = (num) => {
      if (num === null || num === undefined || num === '') return '0'
      
      const number = parseFloat(num)
      if (isNaN(number)) return '0'
      
      // Convert to string and split by decimal point
      const parts = number.toString().split('.')
      const integerPart = parts[0]
      const decimalPart = parts[1] || ''
      
      // Format integer part with Indian numbering
      let formattedInteger = ''
      const length = integerPart.length
      
      // Indian numbering: last 3 digits, then groups of 2
      if (length <= 3) {
        formattedInteger = integerPart
      } else {
        // Get the last 3 digits
        const lastThree = integerPart.slice(-3)
        // Get the remaining digits
        const remaining = integerPart.slice(0, -3)
        
        // Format remaining digits in groups of 2 from right to left
        let formattedRemaining = ''
        for (let i = remaining.length - 1; i >= 0; i -= 2) {
          const start = Math.max(0, i - 1)
          const group = remaining.slice(start, i + 1)
          formattedRemaining = group + (formattedRemaining ? ',' + formattedRemaining : '')
        }
        
        formattedInteger = formattedRemaining + ',' + lastThree
      }
      
      // Add decimal part if exists
      if (decimalPart) {
        return formattedInteger + '.' + decimalPart
      }
      
      return formattedInteger
    }

    // Computed properties for formatted amounts
    const formattedTotalBudget = computed(() => {
      return formatIndianNumber(totalBudgetAmount.value)
    })

    const formattedAllocatedAmount = computed(() => {
      return formatIndianNumber(allocatedAmount.value)
    })

    const viewHistory = () => {
      window.open('/budget-phase-history', '_blank')
    }

    // Computed property for filtered budget heads based on search
    const computedFilteredBudgetHeads = computed(() => {
      if (!searchTerm.value || searchTerm.value.trim() === '') {
        return filteredBudgetHeads.value
      }
      const searchLower = searchTerm.value.toLowerCase().trim()
      return filteredBudgetHeads.value.filter(item => {
        const budget = String(item.budget || '').toLowerCase()
        const description = String(item.description || '').toLowerCase()
        return budget.includes(searchLower) || description.includes(searchLower)
      })
    })

    // Function to clear filters
    const clearFilters = () => {
      searchTerm.value = ''
    }

    // Function to prepare table data for export
    const prepareTableData = () => {
      const data = []
      
      // Add header row
      data.push(['Budget Head', 'Head Description', 'Budget Amount (₹ In Lakhs)'])
      
      // Add data rows
      computedFilteredBudgetHeads.value.forEach(item => {
        data.push([
          item.budget || '',
          item.description || '',
          item.amount || '0.00'
        ])
      })
      
      // Add total row
      const total = computedFilteredBudgetHeads.value.reduce((sum, item) => {
        return sum + (parseFloat(item.amount) || 0)
      }, 0)
      data.push(['', 'Total', total.toFixed(2)])
      
      return data
    }

    // Function to export to Excel (.xlsx)
    const exportToExcel = () => {
      const data = prepareTableData()
      const wb = XLSX.utils.book_new()
      const ws = XLSX.utils.aoa_to_sheet(data)
      XLSX.utils.book_append_sheet(wb, ws, 'Budget Phase Report')
      XLSX.writeFile(wb, `Budget_Phase_Report_${financialYear.value}_${selectedPhase.value}_${new Date().getTime()}.xlsx`)
    }

    // Function to export to CSV
    const exportToCSV = () => {
      const data = prepareTableData()
      let csvContent = ''
      
      data.forEach(row => {
        const csvRow = row.map(cell => {
          const cellValue = String(cell || '')
          if (cellValue.includes(',') || cellValue.includes('"') || cellValue.includes('\n')) {
            return `"${cellValue.replace(/"/g, '""')}"`
          }
          return cellValue
        })
        csvContent += csvRow.join(',') + '\n'
      })
      
      const BOM = '\uFEFF'
      const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      const url = URL.createObjectURL(blob)
      
      link.setAttribute('href', url)
      link.setAttribute('download', `Budget_Phase_Report_${financialYear.value}_${selectedPhase.value}_${new Date().getTime()}.csv`)
      link.style.visibility = 'hidden'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }

    // Function to export to PDF
    const exportToPDF = () => {
      const printWindow = window.open('', '_blank')
      const tableElement = document.getElementById('reportTable')
      
      if (!tableElement) {
        alert('Table not found')
        return
      }
      
      const tableHTML = tableElement.outerHTML
      
      const headStart = '<head>'
      const titleTag = '<title>Budget Phase Report - ' + financialYear.value + ' - ' + selectedPhase.value + '</title>'
      const styleStart = '<style>'
      const styles = 'body { font-family: Arial, sans-serif; margin: 20px; }' +
        'h2 { text-align: center; color: #333; }' +
        '.meta-info { text-align: center; margin-bottom: 20px; color: #666; }' +
        'table { width: 100%; border-collapse: collapse; margin-top: 20px; }' +
        'table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 10px; }' +
        'table th { background-color: #007bff; color: white; font-weight: bold; }' +
        '@media print { @page { size: landscape; margin: 1cm; } body { margin: 0; } }'
      const styleEnd = '</style>'
      const headEnd = '</head>'
      
      const bodyStart = '<body>'
      const h2Tag = '<h2>Budget Phase Report</h2>'
      const metaInfoStart = '<div class="meta-info">'
      const financialYearP = '<p><strong>Financial Year:</strong> ' + financialYear.value + '</p>'
      const phaseP = '<p><strong>Budget Phase:</strong> ' + selectedPhase.value + '</p>'
      const generatedP = '<p><strong>Generated on:</strong> ' + new Date().toLocaleString() + '</p>'
      const metaInfoEnd = '</div>'
      const scriptStart = '<' + 'script' + '>'
      const scriptContent = 'window.onload = function() { window.print(); }'
      const scriptEnd = '<' + '/' + 'script' + '>'
      const scriptTag = scriptStart + scriptContent + scriptEnd
      const bodyEnd = '<' + '/' + 'body' + '>'
      const htmlEnd = '<' + '/' + 'html' + '>'
      
      const htmlContent = '<!DOCTYPE html><html>' +
        headStart + titleTag + styleStart + styles + styleEnd + headEnd +
        bodyStart + h2Tag + metaInfoStart + financialYearP + phaseP + generatedP + metaInfoEnd +
        tableHTML + scriptTag + bodyEnd + htmlEnd
      
      printWindow.document.write(htmlContent)
      printWindow.document.close()
    }

    return {
      selectedPhase,
      financialYear,
      filteredBudgetHeads,
      isSubmitted,
      isProcessing,
      message,
      messageType,
      totalBudgetAmount,
      allocatedAmount,
      remainingAmount,
      fetchBudgetHeads,
      saveAsDraft,
      submit,
      reset,
      clearMessage,
      formatIndianNumber,
      formattedTotalBudget,
      formattedAllocatedAmount,
      viewHistory,
      searchTerm,
      computedFilteredBudgetHeads,
      clearFilters,
      exportToExcel,
      exportToCSV,
      exportToPDF,
      onFinancialYearChange
    }
  }
}
</script>

<style scoped>
/* Export buttons styling */
.export-buttons {
  display: flex;
  gap: 8px;
}

.export-buttons .btn {
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 4px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.export-buttons .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.export-buttons .btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.export-buttons .btn-info {
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.export-buttons .btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}
</style>
