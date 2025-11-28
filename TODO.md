# TODO List

## Completed Tasks
- [x] Fix SL Component List to show Excel-uploaded SLS data
- [x] Update frontend filtering to include records with sls_code
- [x] Add SLS Code column to SL Component List table
- [x] Test that uploaded SLS data appears in SL Component List
- [x] Update SL Component List table to show only: SLS Code, SLS Name, State Name, Sharing Pattern(Centre), Sharing Pattern(State)
- [x] Handle 0/null values in Sharing Pattern columns - store as 0 and display as 0
- [x] Update PD Component List to fetch data from md_program_divisions table instead of pd_and_sls_comp
- [x] Simplify PD Component List to show only S. No. and Division Name columns
- [x] Update PD component saving to md_program_divisions table instead of pd_and_sls_comp

## Current Tasks
- [x] **Update SLS ID functionality**: 
  - [x] Populate PD dropdown from md_program_divisions table when SLS ID is selected
  - [x] Handle SLS data saving with duplicate checking (update if exists, insert if new)
  - [x] Add route for PD components dropdown
  - [x] Update frontend to fetch and display PD components in dropdown
  - [x] Update controller to handle SLS data with proper duplicate checking
  - [x] **Handle new SLS fields**: 
    - [x] Update controller to handle slsCode and slsName fields
    - [x] Update frontend to send both SLS Code and SLS Name in payload
    - [x] Update form initialization to include slsName field
    - [x] Update duplicate checking to use slsCode instead of slsName

## Pending Tasks
- [ ] Test the complete SLS ID workflow
- [ ] Verify PD dropdown population works correctly
- [ ] Test duplicate SLS record handling
- [ ] Ensure proper error handling for all scenarios
