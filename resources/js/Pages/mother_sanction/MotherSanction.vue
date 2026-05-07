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
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="kyMsNo">KY MS No.</label>
                        <div class="input-group">
                          <input type="text" class="form-control" id="kyMsNo" v-model="kyMsNo" placeholder="Enter manually">
                          <!-- <button class="btn btn-outline-secondary" type="button" @click="regenerateKyMsNo" title="Regenerate KY MS No">
                            <i class="fas fa-sync-alt"></i>
                          </button> -->
                        </div>
                      </div>
                    </div>

                    <!-- SLS ID -->
                    <div class="col-md-6 col-lg-3">
                      <div class="form-group">
                        <label for="slsId">SLS Name</label>
                        <select class="form-control" v-model="selectedSlsId" @change="fetchFundAllocationData">
                          <option value="">--- Select SLS Name ---</option>
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
                          <td style="display: none;">
                            <label class="highlight_textbox">Available Fund Amount</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Current Available Fund Amount</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Mother Sanction Amount</label>
                          </td>
                          <td>
                            <label class="highlight_textbox">Carry Forward</label>
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
                          <td style="display: none;">
                            <input type="text" v-model="row.available_amount" class="form-control tableform-control-withoutbg" disabled>
                          </td>
                          <td>
                            <input 
                              type="text" 
                              :value="getCurrentAvailableFundAmount(row)" 
                              class="form-control tableform-control-withoutbg" 
                              disabled
                            >
                          </td>
                          <td>
                           <input 
                              type="number" 
                              v-model="row.sanction_amount" 
                              class="form-control tableform-control-withoutbg"
                              @input="checkSanctionAmount(row)"
                            >
                          </td>
                          <td>
                           <input 
                              type="number" 
                              v-model="row.carry_forward" 
                              class="form-control tableform-control-withoutbg"
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
                          accept=".csv,.pdf,.png,.jpg,.jpeg,text/csv,application/pdf,image/png,image/jpeg"
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
                          accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"
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


const kyMsNo = ref('');

// // Function to generate KY MS No
// const generateKyMsNo = () => {
//   if (!financialYear.value || !selectedState.value || !msSequenceNo.value || !selectedSlsId.value) {
//     return '';
//   }

//   const yearPart = financialYear.value.split('-')[0].slice(-2); // "2024-2025" => "24"
//   const stateCode = stateCodeMap[selectedState.value] || 'XX';   // e.g., 'UP'
//   const sequenceNo = msSequenceNo.value.toString().padStart(2, '0'); // 1 => 01
//   // intead of selectedSlsId.value take key for the selectedSlsId.value from the slsData
//   const sls = slsData.value.find(sls => sls.name === selectedSlsId.value)?.id;
//   // const sls = selectedSlsId.value;
  
//   return `MS${yearPart}${stateCode}${sequenceNo}${sls}`;
// };

// // Watch for changes in required fields to auto-generate KY MS No
// watch([financialYear, selectedState, msSequenceNo, selectedSlsId], () => {
//   if (financialYear.value && selectedState.value && msSequenceNo.value && selectedSlsId.value) {
//     // Only auto-generate if the field is empty or if it matches the previous generated pattern
//     if (!kyMsNo.value || kyMsNo.value.startsWith('MS')) {
//       kyMsNo.value = generateKyMsNo();
//     }
//   }
// });

// // Watch for slsData changes to regenerate KY MS No when SLS data is loaded
// watch(slsData, () => {
//   if (financialYear.value && selectedState.value && msSequenceNo.value && selectedSlsId.value && slsData.value.length > 0) {
//     // Only auto-generate if the field is empty or if it matches the previous generated pattern
//     if (!kyMsNo.value || kyMsNo.value.startsWith('MS')) {
//       kyMsNo.value = generateKyMsNo();
//     }
//   }
// });

// // Function to manually regenerate KY MS No
// const regenerateKyMsNo = () => {
//   kyMsNo.value = generateKyMsNo();
// };

const resetForm = () => {
  financialYear.value = '';
  selectedState.value = '';
  msSequenceNo.value = '';
  // sanctionNo.value = '';
  remark.value = '';
  ifdNo.value = '';
  sanctionDate.value = '';
  kyMsNo.value = '';
  selectedSlsId.value = '';
  pdComponent.value = '';

  if (ucFilePreview.value) URL.revokeObjectURL(ucFilePreview.value);
  if (sanctionFilePreview.value) URL.revokeObjectURL(sanctionFilePreview.value);

  ucFile.value = null;
  sanctionFile.value = null;
  ucFilePreview.value = null;
  sanctionFilePreview.value = null;

  reappropriations.value = [{ budget_head: '', category: '', available_amount: '', sanction_amount: '', carry_forward: '0.00000' }];
  
  // Clear fund allocations when form is reset
  fundAllocations.value = [];
  
  // Hide any existing flash messages
  hideFlashMessage();
};







const reappropriations = ref([
  { budget_head: '', category: '', available_amount: '', sanction_amount: '', carry_forward: '0.00000' }
])

// Store released amounts for each budget head and pd_component combination
const releasedAmounts = ref({})

// Function to fetch total M.S Release for a budget head (regardless of pd_component)
const fetchReleasedAmount = async (budgetHead) => {
  if (!budgetHead) {
    return 0;
  }
  
  const cacheKey = budgetHead; // Use only budget_head as key
  
  // Return cached value if available
  if (releasedAmounts.value[cacheKey] !== undefined) {
    return releasedAmounts.value[cacheKey];
  }
  
  // Fetch from API - get total M.S Release for this budget head (all pd_components)
  try {
    const response = await fetch(
      `/api/mother-sanction/released-amount?budget_head=${encodeURIComponent(budgetHead)}`
    );
    
    if (response.ok) {
      const data = await response.json();
      const released = parseFloat(data.total_released || 0);
      
      // Cache the released amount
      releasedAmounts.value[cacheKey] = released;
      return released;
    } else {
      // If API fails, cache 0
      releasedAmounts.value[cacheKey] = 0;
      return 0;
    }
  } catch (error) {
    console.error('Error fetching released amount:', error);
    // If error, cache 0
    releasedAmounts.value[cacheKey] = 0;
    return 0;
  }
}

// Function to get current available fund amount
// Formula: Current Available Fund Amount = (Total Allocation corresponding to the budget head) - (Total M.S Release)
const getCurrentAvailableFundAmount = (row) => {
  if (!row.budget_head) {
    return '0.00000';
  }
  
  // Total Allocation = available_amount (sum of all allocations for this budget head corresponding to SLS and state)
  const totalAllocation = parseFloat(row.available_amount) || 0;
  
  // Total M.S Release = sum of all mother_sanction_amount for this budget head (regardless of pd_component)
  const cacheKey = row.budget_head; // Use only budget_head as key for total M.S Release
  const totalMsRelease = releasedAmounts.value[cacheKey] || 0;
  
  // Current Available Fund Amount = Total Allocation - Total M.S Release
  const currentAvailable = totalAllocation - totalMsRelease;
  
  // Format to 5 decimal places
  return currentAvailable >= 0 ? currentAvailable.toFixed(5) : '0.00000';
}

const handleUcFileChange = (e) => {
  const file = e.target.files[0];
  const validation = validateUploadFile(file, ['csv', 'pdf', 'png', 'jpg', 'jpeg']);
  if (!validation.valid) {
    e.target.value = '';
    ucFile.value = null;
    ucFilePreview.value = null;
    showFlashMessage('danger', validation.message, 'fas fa-exclamation-triangle');
    return;
  }

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
  const validation = validateUploadFile(file, ['pdf', 'png', 'jpg', 'jpeg']);
  if (!validation.valid) {
    e.target.value = '';
    sanctionFile.value = null;
    sanctionFilePreview.value = null;
    showFlashMessage('danger', validation.message, 'fas fa-exclamation-triangle');
    return;
  }

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
  const safeFinancialYear = sanitizeTextInput(financialYear.value);
  const safeIfdNo = sanitizeTextInput(ifdNo.value);
  const safeKyMsNo = sanitizeTextInput(kyMsNo.value);
  const safeSlsName = sanitizeTextInput(selectedSlsId.value);
  const safePdComponent = sanitizeTextInput(pdComponent.value);
  const safeRemark = sanitizeTextInput(remark.value);

  formData.append('financial_year', financialYear.value);
  formData.append('state_id', selectedState.value);
  formData.append('ms_sequence_no', msSequenceNo.value);
  // formData.append('file_no', sanctionNo.value);
  formData.append('remark', safeRemark);
  formData.append('ifd_no', safeIfdNo);
  formData.append('sanction_date', sanctionDate.value);
  formData.append('ky_ms_no', safeKyMsNo);

  formData.append('sls_name', safeSlsName);
  formData.append('pd_component', safePdComponent);
  formData.set('financial_year', safeFinancialYear);
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

  // Handle revise mode: MS amount = (MS amount field value + Carry Forward field value)
  const urlParams = new URLSearchParams(window.location.search);
  const isRevise = urlParams.get('revise') === 'true';
  
  let reappropriationsToSubmit = reappropriations.value;
  if (isRevise) {
    reappropriationsToSubmit = reappropriations.value.map(row => {
      const msAmount = parseFloat(row.sanction_amount) || 0;
      const carryForward = parseFloat(row.carry_forward) || 0;
      const finalMsAmount = msAmount + carryForward;
      
      return {
        ...row,
        budget_head: sanitizeTextInput(row.budget_head),
        category: sanitizeTextInput(row.category),
        sanction_amount: finalMsAmount.toString()
      };
    });
  } else {
    reappropriationsToSubmit = reappropriations.value.map(row => ({
      ...row,
      budget_head: sanitizeTextInput(row.budget_head),
      category: sanitizeTextInput(row.category),
    }));
  }
  
  formData.append('reappropriations', JSON.stringify(reappropriationsToSubmit));

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
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    // Parse JSON response
    let result = {};
    try {
      const responseText = await response.text();
      if (responseText) {
        result = JSON.parse(responseText);
      }
    } catch (jsonError) {
      console.error('Error parsing response:', jsonError);
      showFlashMessage('danger', 'Invalid response from server. Please try again.', 'fas fa-exclamation-triangle');
      return;
    }
    
    if (response.ok && result.message) {
      // Success response
      if (status === 1) {
        showFlashMessage('success', result.message || 'Data submitted successfully!', 'fas fa-check-circle');
      } else {
        showFlashMessage('info', result.message || 'Data saved as draft successfully!', 'fas fa-save');
      }
      
      // Reset form after successful submission
      setTimeout(() => {
        resetForm();
      }, 1500); // Delay reset to allow user to see the success message
    } else {
      // Handle error responses
      if (response.status === 422 && result.errors) {
        // Validation errors
        const errorMessages = Object.values(result.errors).flat();
        const errorMessage = errorMessages.join(', ');
        showFlashMessage('danger', `Validation failed: ${errorMessage}`, 'fas fa-exclamation-triangle');
      } else {
        // Other errors - check for message or error field
        const errorMessage = result.message || result.error || 'An error occurred while saving the data.';
        showFlashMessage('danger', errorMessage, 'fas fa-exclamation-triangle');
      }
    }
  } catch (error) {
    console.error('Network error:', error);
    showFlashMessage('danger', 'Network error. Please check your connection and try again.', 'fas fa-exclamation-triangle');
  }
};

const sanitizeTextInput = (value) => {
  return String(value ?? '')
    .replace(/<[^>]*>/g, '')
    .replace(/[\u0000-\u001F\u007F]/g, '')
    .trim();
};

const validateUploadFile = (file, allowedExtensions) => {
  if (!file) {
    return { valid: true };
  }

  const extension = (file.name.split('.').pop() || '').toLowerCase();
  if (!allowedExtensions.includes(extension)) {
    return {
      valid: false,
      message: `Invalid file type. Allowed: ${allowedExtensions.join(', ').toUpperCase()}.`,
    };
  }

  const maxFileSize = 10 * 1024 * 1024;
  if (file.size > maxFileSize) {
    return { valid: false, message: 'File exceeds 10MB size limit.' };
  }

  return { valid: true };
};



function addReappropriationRow() {
  reappropriations.value.push({
    budget_head: '',
    category: '',
    available_amount: '',
    sanction_amount: '',
    carry_forward: '0.00000'
  })
}

// Function to prefill form from URL parameters
const prefillFormFromURL = async () => {
  const urlParams = new URLSearchParams(window.location.search);
  
  if (urlParams.get('edit') === 'true' || urlParams.get('revise') === 'true') {
    console.log('Prefilling form from URL parameters');
    
    // Prefill basic fields
    if (urlParams.get('financial_year')) {
      financialYear.value = urlParams.get('financial_year');
    }
    
    if (urlParams.get('state_id')) {
      selectedState.value = urlParams.get('state_id');
      // Fetch SLS data for the selected state
      await fetchSlsData();
    }
    
    if (urlParams.get('sls_name')) {
      selectedSlsId.value = urlParams.get('sls_name');
      // Fetch fund allocation data
      await fetchFundAllocationData();
    }
    
    // if (urlParams.get('ms_sequence_no')) {
    //   const msSeqNo = parseInt(urlParams.get('ms_sequence_no'));
    //   if (!isNaN(msSeqNo)) {
    //     msSequenceNo.value = (msSeqNo + 1).toString();
    //   } else {
    //     msSequenceNo.value = urlParams.get('ms_sequence_no');
    //   }
    // }

    if (urlParams.get('ms_sequence_no')) {
        msSequenceNo.value = urlParams.get('ms_sequence_no');
    }
    
    if (urlParams.get('sanction_date')) {
      sanctionDate.value = urlParams.get('sanction_date');
    }
    
    if (urlParams.get('ifd_no')) {
      ifdNo.value = urlParams.get('ifd_no');
    }
    
    if (urlParams.get('ky_ms_no')) {
      kyMsNo.value = urlParams.get('ky_ms_no');
    }
    
    // if (urlParams.get('sls_name')) {
    //   selectedSlsId.value = urlParams.get('sls_name');
    // }
    
    if (urlParams.get('pd_component')) {
      pdComponent.value = urlParams.get('pd_component');
    }
    
    if (urlParams.get('remark')) {
      remark.value = urlParams.get('remark');
    }
    
    // Handle budget heads data from URL parameters
    if (urlParams.get('budget_heads')) {
      try {
        const budgetHeadsData = JSON.parse(urlParams.get('budget_heads'));
        if (Array.isArray(budgetHeadsData) && budgetHeadsData.length > 0) {
          const isRevise = urlParams.get('revise') === 'true';
          
          reappropriations.value = budgetHeadsData.map(budget => {
            if (isRevise) {
              // For revise: carry_forward = available amount, sanction_amount = MS amount
              return {
                budget_head: budget.budget_head || '',
                category: budget.category || '',
                available_amount: budget.available_amount || '', // This will be fetched from fund allocation
                sanction_amount: budget.sanction_amount || '', // MS amount field
                carry_forward: budget.carry_forward || '0.00000' // Available amount in carry forward
              };
            } else {
              // For edit: normal prefilling
              return {
                budget_head: budget.budget_head || '',
                category: budget.category || '',
                available_amount: budget.available_fund || budget.available_amount || '',
                sanction_amount: budget.mother_sanction_amount || budget.sanction_amount || '',
                carry_forward: budget.carry_forward || '0.00000'
              };
            }
          });
          
          // After setting budget heads, fetch available amounts for revise mode
          if (isRevise && selectedSlsId.value && selectedState.value) {
            await fetchFundAllocationData();
            // Update available_amount for each row after fund allocation is fetched
            for (const row of reappropriations.value) {
              if (row.budget_head) {
                await fetchBudgetDetails(row);
              }
            }
          } else if (selectedSlsId.value && selectedState.value) {
            // For edit mode, also fetch released amounts for all budget heads
            for (const row of reappropriations.value) {
              if (row.budget_head) {
                await fetchReleasedAmount(row.budget_head);
              }
            }
          }
        }
      } catch (error) {
        console.error('Error parsing budget heads data:', error);
      }
    }
    
    // If we have a KY MS No, fetch the detailed data
    if (urlParams.get('ky_ms_no')) {
      await fetchMotherSanctionDetails(urlParams.get('ky_ms_no'));
    }
    
    // Regenerate KY MS No after all data is loaded
    if (financialYear.value && selectedState.value && msSequenceNo.value && selectedSlsId.value) {
      kyMsNo.value = generateKyMsNo();
    }
  }
}

// Function to fetch mother sanction details for prefilling
const fetchMotherSanctionDetails = async (kyMsNo) => {
  try {
    const response = await fetch(`/api/mother-sanction-details/${kyMsNo}`);
    if (response.ok) {
      const data = await response.json();
      console.log('Fetched mother sanction details:', data);
      
      // Prefill the reappropriations table with the fetched data
      if (data.entries && data.entries.length > 0) {
        reappropriations.value = data.entries.map(entry => ({
          budget_head: entry.budget_head,
          category: entry.category,
          available_amount: entry.available_fund,
          sanction_amount: entry.mother_sanction_amount,
          carry_forward: entry.carry_forward || '0.00000'
        }));
      }
      
      // Prefill other fields if available
      if (data.meta) {
        if (data.meta.ifd_no && !ifdNo.value) {
          ifdNo.value = data.meta.ifd_no;
        }
        if (data.meta.sls_name && !selectedSlsId.value) {
          selectedSlsId.value = data.meta.sls_name;
        }
        if (data.meta.pd_component && !pdComponent.value) {
          pdComponent.value = data.meta.pd_component;
        }
        if (data.meta.ms_sequence_no && !msSequenceNo.value) {
          const msSeqNo = parseInt(data.meta.ms_sequence_no);
          if (!isNaN(msSeqNo)) {
            msSequenceNo.value = (msSeqNo + 1).toString();
          } else {
            msSequenceNo.value = data.meta.ms_sequence_no;
          }
        }
      }
    }
  } catch (error) {
    console.error('Error fetching mother sanction details:', error);
  }
}

onMounted(async () => {
  try {
    const [statesRes, budgetHeadsRes] = await Promise.all([
      fetch('/api/states', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      }),
      fetch('/api/budget-heads', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      })
    ]);

    if (statesRes.ok) {
      states.value = await statesRes.json();
    }

    if (budgetHeadsRes.ok) {
      budgetHeads.value = await budgetHeadsRes.json();
    }

    // Check for URL parameters to prefill form
    prefillFormFromURL();

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
        // Sum all amounts to get Total Allocation for this budget head
        const totalAmount = data.reduce((sum, item) => {
          return sum + (parseFloat(item.amount) || 0);
        }, 0);
        
        // Use the first item for category (should be same for all)
        const budgetData = data[0];
        row.category = budgetData.category || '';
        row.available_amount = totalAmount.toFixed(5); // Total Allocation
      } else if (data && typeof data === 'object') {
        // Handle single object response
        row.category = data.category || '';
        row.available_amount = data.amount || '';
      } else {
        // No data found
        clearRowData(row);
        console.log('No budget details found for the selected budget head');
      }
      
      // After fetching budget details, fetch total M.S Release for current available fund calculation
      if (row.budget_head) {
        await fetchReleasedAmount(row.budget_head);
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
  // Clear released amounts cache when SLS or state changes
  releasedAmounts.value = {};
});

// Watch for changes in budget heads to refresh released amounts
watch(() => reappropriations.value.map(r => r.budget_head), () => {
  // Clear released amounts cache when budget heads change
  releasedAmounts.value = {};
  // Re-fetch released amounts for all rows with budget heads
  reappropriations.value.forEach(async (row) => {
    if (row.budget_head) {
      await fetchReleasedAmount(row.budget_head);
    }
  });
}, { deep: true });

const clearRowData = (row) => {
  row.category = '';
  row.available_amount = '';
  row.sanction_amount = '';
  row.carry_forward = '0.00000';
};


</script>

<style scoped>
/* Kaiadmin theme sets `.main-panel > .container { overflow: hidden; }` which clips native select dropdowns. */
.main-panel > .container {
  overflow: visible !important;
}

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

/* Input group styling for KY MS No */
.input-group .btn {
  border-left: 0;
}

.input-group .form-control:focus + .btn {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
