<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Mother Sanction Module</div>

                </div>

                <div class="card-body">
                  <!-- Flash Message -->
                  <div v-if="flashMessage.show" :class="`alert alert-${flashMessage.type} alert-dismissible fade show`" role="alert">
                    <i :class="flashMessage.icon"></i>
                    {{ flashMessage.message }}
                    <button type="button" class="btn-close" @click="hideFlashMessage" aria-label="Close"></button>
                  </div>

                  <div class="row">
                    <!-- Financial Year -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="financialYear">F.Y</label>
                        <select class="form-select" v-model="financialYear">
                          <option disabled value="">Select Financial Year</option>
                          <option value="2024-2025">2025–2026</option>
                          <option value="2024-2025">2024–2025</option>
                        </select>
                      </div>
                    </div>

                    <!-- State -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="stateSelect">State</label>
                       <select v-model="selectedState" @change="fetchSlsData" class="form-select" id="stateSelect">
                          <option value="">--- Select State ---</option>
                          <option v-for="state in states" :key="state.id" :value="state.id">
                            {{ state.name }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <!-- MS Sequence No. -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="msSequence">MS Sequence No.</label>
                        <select class="form-select" v-model="msSequenceNo" id="msSequence">
                          <option value="">Select</option>
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                        </select>
                      </div>
                    </div>

                    <!-- Sanction/File No -->
                    <!-- <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="sanctionNo">Sanction No./File No.</label>
                        <input type="text" class="form-control" id="sanctionNo" v-model="sanctionNo" placeholder="67890">
                      </div>
                    </div> -->

                     <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                          <label for="ifdNo">IFD No.</label>
                          <input type="text" class="form-control" id="ifdNo" v-model="ifdNo">
                        </div>
                      </div>

                    <!-- Sanction Date -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="sanctionDate">Sanction Date</label>
                        <input type="date" class="form-control" id="sanctionDate" v-model="sanctionDate">
                      </div>
                    </div>

                    <!-- KY MS No -->
                    <!-- <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="kyMsNo">KY MS No.</label>
                        <input type="text" class="form-control" id="kyMsNo" :value="kyMsNo" disabled>


                      </div>
                    </div> -->

                    <!-- SLS ID -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="slsId">SLS ID</label>
                        <select class="form-control" v-model="selectedSlsId" @change="fetchFundAllocationData">
                          <option value="">--- Select SLS ID ---</option>
                          <option v-for="sls in slsData" :key="sls.id" :value="sls.name">{{ sls.name }}</option>
                        </select>
                      </div>
                    </div>

                    <!-- PD/Component -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="pdComponent">PD/Component</label>
                        <input
                            type="text"
                            class="form-control"
                            id="pdComponent"
                            v-model="pdComponent"
                            disabled 
                          >
                      </div>
                    </div>

                   

                    <!-- Total Sanction Amount -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="totalSanction">Total Mother Sanction Amount(current)</label>
                        <input type="text" class="form-control" id="totalSanction" :value="totalSanctionAmount.toLocaleString()" disabled>

                      </div>
                    </div>
                  </div>

                  <!-- Remark  -->
                  <div class="col-md-6 col-lg-3">
                    <div class="form-group">
                      <label for="remark">Remark</label>
                      <input type="text" class="form-control" id="remark" v-model="remark" placeholder="Enter Remark">
                    </div>
                  </div>

                  <!-- Budget Table -->
                  <div class="table-responsive mt-3">
                    <table class="table table-bordered table-head-bg-primary">
                      <tbody>
                        <tr>
                          <td width="20%">
                            <label class="highlight_textbox">Budget Head</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Category</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Available Fund Amount</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Mother Sanction Amount</label>
                          </td>
                        </tr>

                        <!-- Example Rows -->
                        <tr v-for="(row, index) in reappropriations" :key="index">
                          <!-- <td>
                            {{ fundAllocations }}
                          </td> -->
                          <td>
                            <select v-model="row.budget_head" class="form-select" @change="fetchBudgetDetails(row)">
                              <option value="">--- Budget Head ---</option>
                              <option 
                                v-for="(item, idx) in fundAllocations.filter(f => !selectedBudgetHeads.includes(f.budget) || f.budget === row.budget_head)"
                                :key="idx" 
                                :value="item.budget"
                              >
                                {{ item.budget }}
                              </option>
                            </select>
                          </td>
                          <td>
                            <input type="text" v-model="row.category" class="form-control tableform-control-withoutbg" disabled>
                          </td>
                          <td>
                            <input type="text" v-model="row.available_amount" class="form-control tableform-control-withoutbg" disabled>
                          </td>
                          <td>
                           <input 
                              type="number" 
                              v-model="row.sanction_amount" 
                              class="form-control tableform-control-withoutbg"
                              @input="checkSanctionAmount(row)"
                            >
                          </td>
                          <td class="text-center">
                            <button class="btn btn-sm btn-danger" @click="removeReappropriationRow(index)" v-if="reappropriations.length > 1">×</button>
                          </td>
                        </tr>
                       
                      </tbody>
                    </table>
                    <button class="btn btn-primary me-1 mb-4" @click="addReappropriationRow">
                      + Add More
                    </button>

                  </div>

                  <!-- File Uploads -->
                  <div class="bg-body-secondary mt-4 mb-4 rebdr">
                    <div class="row">
                      <div class="col-md-6 col-lg-6">
                        <div class="form-group">
                          <label for="ucFile">UC Received From State</label>
                          <input type="file" class="form-control" id="ucFile"
                          @change="handleUcFileChange" name="uc_file_path" />
                        </div>
                          <!-- UC File Preview -->
                      <div v-if="ucFilePreview" class="mt-2">
                        <template v-if="ucFile?.type.startsWith('image/')">
                          <img :src="ucFilePreview" alt="UC File Preview" style="max-width: 200px;" />
                        </template>
                        <template v-else>
                          <a :href="ucFilePreview" target="_blank" class="btn btn-outline-primary btn-sm">Preview UC File</a>
                        </template>
                      </div>
                      </div>
                    
                      <div class="col-md-6 col-lg-6">
                        <div class="form-group">
                          <label for="sanctionFile">Signed Copy of Mother Sanction</label>
                          <input type="file" class="form-control" id="sanctionFile"
                          @change="handleSanctionFileChange" name="signed_copy_path" />
                        </div>
                        <div v-if="sanctionFilePreview" class="mt-2">
                        <template v-if="sanctionFile?.type.startsWith('image/')">
                          <img :src="sanctionFilePreview" alt="Sanction File Preview" style="max-width: 200px;" />
                        </template>
                        <template v-else>
                          <a :href="sanctionFilePreview" target="_blank" class="btn btn-outline-primary btn-sm">Preview Sanction File</a>
                        </template>
                      </div>
                      </div>
                      
                    </div>
                  </div>
                </div>

                <!-- Footer Buttons -->
                <div class="card-footer">
                  <div class="form">
                    <div class="col-12 d-flex justify-content-center">
                      <button class="btn btn-primary me-1" @click="submitData(0)">Save as Draft</button>
                      <button class="btn btn-success me-1" @click="submitData(1)">Submit</button>
                      <button class="btn btn-danger me-1" @click="resetForm">Reset</button>

                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed , watch} from 'vue'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue' 


const states = ref([])
const selectedState = ref('')
const budgetHeads = ref([])
const selectedSlsId = ref('');
const slsData = ref([]);
const fundAllocations = ref([]);
const financialYear = ref('');
const msSequenceNo = ref('');
// const sanctionNo = ref('');
const remark = ref('');
const ifdNo = ref('');
const sanctionDate = ref('');
//const kyMsNo = ref('');
const pdComponent = ref('');
const ucFile = ref(null);
const sanctionFile = ref(null);
const ucFilePreview = ref(null)
const sanctionFilePreview = ref(null)

// Flash message state
const flashMessage = ref({
  show: false,
  type: 'success',
  message: '',
  icon: ''
});

const showFlashMessage = (type, message, icon) => {
  flashMessage.value = {
    show: true,
    type,
    message,
    icon
  };
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideFlashMessage();
  }, 5000);
};

const hideFlashMessage = () => {
  flashMessage.value.show = false;
};

const stateCodeMap = {
  1: 'UP',
  2: 'MH',
  3: 'RJ',
  4: 'OR',
  // Replace with your actual state.id and 2-letter codes
};


const kyMsNo = computed(() => {
  if (!financialYear.value || !selectedState.value || !msSequenceNo.value || !selectedSlsId.value) {
    return '';
  }

  const yearPart = financialYear.value.split('-')[0].slice(-2); // "2024-2025" => "24"
  const stateCode = selectedState.value || 'XX';   // e.g., 'UP'
  const sequenceNo = msSequenceNo.value.toString().padStart(2, '0'); // 1 => 01
  const sls = selectedSlsId.value;

  return `MS${yearPart}${stateCode}${sequenceNo}${sls}`;
});


const resetForm = () => {
  financialYear.value = '';
  selectedState.value = '';
  msSequenceNo.value = '';
  // sanctionNo.value = '';
  remark.value = '';
  ifdNo.value = '';
  sanctionDate.value = '';
  //kyMsNo.value = '';
  selectedSlsId.value = '';
  pdComponent.value = '';

  if (ucFilePreview.value) URL.revokeObjectURL(ucFilePreview.value);
  if (sanctionFilePreview.value) URL.revokeObjectURL(sanctionFilePreview.value);

  ucFile.value = null;
  sanctionFile.value = null;
  ucFilePreview.value = null;
  sanctionFilePreview.value = null;

  reappropriations.value = [{ budget_head: '', category: '', available_amount: '', sanction_amount: '' }];
  
  // Clear fund allocations when form is reset
  fundAllocations.value = [];
  
  // Hide any existing flash messages
  hideFlashMessage();
};







const reappropriations = ref([
  { budget_head: '', category: '', available_amount: '', sanction_amount: '' }
])

const handleUcFileChange = (e) => {
  const file = e.target.files[0];
  ucFile.value = file;
  if (file) {
    ucFilePreview.value = URL.createObjectURL(file);
  }
};



const checkSanctionAmount = (row) => {
  const sanction = parseFloat(row.sanction_amount);
  const available = parseFloat(row.available_amount);

  if (!isNaN(sanction) && !isNaN(available) && sanction > available) {
    alert("    Sanction amount exceeds available funds!");
    row.sanction_amount = ''; // Optionally reset the value
  }
};

const selectedBudgetHeads = computed(() =>
  reappropriations.value.map(row => row.budget_head).filter(Boolean)
);




const handleSanctionFileChange = (e) => {
  const file = e.target.files[0];
  sanctionFile.value = file;
  if (file) {
    sanctionFilePreview.value = URL.createObjectURL(file);
  }
};


function removeReappropriationRow(index) {
  reappropriations.value.splice(index, 1);
}


const submitData = async (status) => {
  // Client-side validation
  // if (!financialYear.value || !selectedState.value || !msSequenceNo.value || !sanctionNo.value || 
  if (!financialYear.value || !selectedState.value || !msSequenceNo.value  || 
      !ifdNo.value || !sanctionDate.value || !selectedSlsId.value || !pdComponent.value) {
    showFlashMessage('danger', 'Please fill in all required fields before submitting.', 'fas fa-exclamation-triangle');
    return;
  }

  // Check if at least one budget row has data
  const hasBudgetData = reappropriations.value.some(row => 
    row.budget_head && row.sanction_amount && parseFloat(row.sanction_amount) > 0
  );
  
  if (!hasBudgetData) {
    showFlashMessage('danger', 'Please add at least one budget allocation with sanction amount.', 'fas fa-exclamation-triangle');
    return;
  }

  const formData = new FormData();
  formData.append('financial_year', financialYear.value);
  formData.append('state_id', selectedState.value);
  formData.append('ms_sequence_no', msSequenceNo.value);
  // formData.append('file_no', sanctionNo.value);
  formData.append('remark', remark.value);
  formData.append('ifd_no', ifdNo.value);
  formData.append('sanction_date', sanctionDate.value);
  // formData.append('ky_ms_no', kyMsNo.value);

  formData.append('sls_name', selectedSlsId.value);
  formData.append('pd_component', pdComponent.value);
  formData.append('total_mother_sanction_amount', totalSanctionAmount.value);
  formData.append('status', status);
  
  // Handle file uploads - ensure files are properly appended
  if (ucFile.value && ucFile.value instanceof File) {
    formData.append('uc_file_path', ucFile.value);
    console.log('UC File appended:', ucFile.value.name);
  } else {
    // If no file is uploaded, send an empty string to avoid null constraint violation
    formData.append('uc_file_path', '');
    console.log('UC File not selected, sending empty string');
  }
  
  if (sanctionFile.value && sanctionFile.value instanceof File) {
    formData.append('signed_copy_path', sanctionFile.value);
    console.log('Sanction File appended:', sanctionFile.value.name);
  } else {
    // If no file is uploaded, send an empty string to avoid null constraint violation
    formData.append('signed_copy_path', '');
    console.log('Sanction File not selected, sending empty string');
  }

  formData.append('reappropriations', JSON.stringify(reappropriations.value));

  // Debug: Log all FormData entries
  console.log('FormData contents:');
  for (let [key, value] of formData.entries()) {
    console.log(`${key}:`, value);
  }

  try {
    const response = await fetch(route('addMotherSanction'), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      }
    });

    if (response.ok) {
      const result = await response.json();
      
      if (status === 1) {
        showFlashMessage('success', 'Data submitted successfully!', 'fas fa-check-circle');
      } else {
        showFlashMessage('info', 'Data saved as draft successfully!', 'fas fa-save');
      }
      
      // Reset form after successful submission
      resetForm();
    } else {
      // Handle HTTP error responses
      const errorData = await response.json().catch(() => ({}));
      
      if (response.status === 422 && errorData.errors) {
        // Validation errors
        const errorMessages = Object.values(errorData.errors).flat();
        const errorMessage = errorMessages.join(', ');
        showFlashMessage('danger', `Validation failed: ${errorMessage}`, 'fas fa-exclamation-triangle');
      } else {
        // Other errors
        const errorMessage = errorData.message || 'An error occurred while saving the data.';
        showFlashMessage('danger', errorMessage, 'fas fa-exclamation-triangle');
      }
    }
  } catch (error) {
    console.error('Network error:', error);
    showFlashMessage('danger', 'Network error. Please check your connection and try again.', 'fas fa-exclamation-triangle');
  }
};



function addReappropriationRow() {
  reappropriations.value.push({
    budget_head: '',
    category: '',
    available_amount: '',
    sanction_amount: ''
  })
}

onMounted(async () => {
  try {
    const [statesRes, budgetHeadsRes] = await Promise.all([
      fetch('/api/states'),
      fetch('/api/budget-heads')
    ]);

    if (statesRes.ok) {
      states.value = await statesRes.json();
    }

    if (budgetHeadsRes.ok) {
      budgetHeads.value = await budgetHeadsRes.json();
    }

  } catch (error) {
    console.error('Fetch error:', error)
  }
})

async function fetchSlsData() {
  if (!selectedState.value) {
    slsData.value = [];
    return;
  }

  try {
    const res = await fetch(`/api/sls-data/${selectedState.value}`);
    if (res.ok) {
      slsData.value = await res.json();
    } else {
      console.error('Failed to fetch SLS data');
    }
  } catch (error) {
    console.error('Error fetching SLS data:', error);
  }
}




const totalSanctionAmount = computed(() => {
  return reappropriations.value.reduce((sum, row) => {
    const amount = parseFloat(row.sanction_amount);
    return sum + (isNaN(amount) ? 0 : amount);
  }, 0);
});

const fetchFundAllocationData = async () => {
  if (!selectedSlsId.value || !selectedState.value) return;
// console.log("selectedSlsId.value",selectedSlsId.value);
// console.log("selectedState.value",selectedState.value);
  try {
    const response = await fetch(`/api/fund-allocation/${selectedSlsId.value}/${selectedState.value}`);
    if (response.ok) {
      const data = await response.json();
      fundAllocations.value = data;
console.log("fundAllocations.value",fundAllocations.value);
      // ✅ Set PD/Component from the first item
      if (data.length > 0) {
        pdComponent.value = data[0].slsPD;
      } else {
        pdComponent.value = '';
      }
    } else {
      console.error('Failed to fetch fund allocation data');
      fundAllocations.value = [];
      pdComponent.value = '';
    }
  } catch (error) {
    console.error('Error fetching fund allocation data:', error);
    fundAllocations.value = [];
    pdComponent.value = '';
  }
};


const fetchBudgetDetails = async (row) => {
  // If budget head is cleared, clear the row data
  if (!row.budget_head) {
    clearRowData(row);
    return;
  }
  
  // If required fields are missing, clear the row data
  if (!selectedSlsId.value || !selectedState.value) {
    clearRowData(row);
    return;
  }

  try {
    const res = await fetch(`/api/fund-allocation/by-budget?budget=${encodeURIComponent(row.budget_head)}&sls_id=${encodeURIComponent(selectedSlsId.value)}&state_id=${selectedState.value}`);
    if (res.ok) {
      const data = await res.json();
      
      // Check if data is an array and has items
      if (Array.isArray(data) && data.length > 0) {
        // Use the first item from the array
        const budgetData = data[0];
        row.category = budgetData.category || '';
        row.available_amount = budgetData.amount || '';
      } else if (data && typeof data === 'object') {
        // Handle single object response
        row.category = data.category || '';
        row.available_amount = data.amount || '';
      } else {
        // No data found
        clearRowData(row);
        console.log('No budget details found for the selected budget head');
      }
    } else {
      clearRowData(row);
      console.error('Budget details not found');
    }
  } catch (error) {
    console.error('Error fetching budget details:', error);
    clearRowData(row);
  }
};

const clearAllBudgetDetails = () => {
  reappropriations.value.forEach(row => {
    clearRowData(row);
  });
};

// Watch for changes in SLS ID or state to clear budget details
watch([selectedSlsId, selectedState], () => {
  clearAllBudgetDetails();
});

const clearRowData = (row) => {
  row.category = '';
  row.available_amount = '';
  row.sanction_amount = '';
};


</script>

<style scoped>
/* Custom styling for flash messages */
.alert {
  border-radius: 8px;
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-success {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  color: #155724;
  border-left: 4px solid #28a745;
}

.alert-info {
  background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
  color: #0c5460;
  border-left: 4px solid #17a2b8;
}

.alert-danger {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  color: #721c24;
  border-left: 4px solid #dc3545;
}

.alert-warning {
  background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
  color: #856404;
  border-left: 4px solid #ffc107;
}

.alert i {
  margin-right: 8px;
  font-size: 1.1em;
}

.btn-close {
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-close:hover {
  opacity: 1;
}

/* Loading button styles */
.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Form validation styles */
.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}
</style>
