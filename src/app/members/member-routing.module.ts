import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { MemberRoutingPage } from './member-routing.page';

const routes: Routes = [
  { path: 'dashboard', loadChildren: './dashboard/dashboard.module#DashboardPageModule' },
  { path: 'clockin', loadChildren: './clockin/clockin.module#ClockinPageModule' },
  { path: 'timesheets', loadChildren: './hours/timesheets/timesheets.module#TimesheetsPageModule' },
  { path: 'overtime', loadChildren: './hours/overtime/overtime.module#OvertimePageModule' },
  { path: 'leaves', loadChildren: './leaves/leaves/leaves.module#LeavesPageModule' },
  { path: 'payslip', loadChildren: './paycheck/payslip/payslip.module#PayslipPageModule' },
  { path: 'allowances', loadChildren: './earnings/allowances/allowances.module#AllowancesPageModule' },
  { path: 'commissions', loadChildren: './earnings/commissions/commissions.module#CommissionsPageModule' },
  { path: 'deminimis', loadChildren: './earnings/deminimis/deminimis.module#DeminimisPageModule' },
  { path: 'contributions', loadChildren: './deductions/contributions/contributions.module#ContributionsPageModule' },
  { path: 'withholdingtax', loadChildren: './deductions/withholdingtax/withholdingtax.module#WithholdingtaxPageModule' },
  { path: 'govloans', loadChildren: './deductions/govloans/govloans.module#GovloansPageModule' },
  { path: 'thirdloans', loadChildren: './deductions/thirdloans/thirdloans.module#ThirdloansPageModule' },
  { path: 'otherdeductions', loadChildren: './deductions/otherdeductions/otherdeductions.module#OtherdeductionsPageModule' },
  { path: 'shift', loadChildren: './shifts/shift/shift.module#ShiftPageModule' },
  { path: 'docu', loadChildren: './documents/docu/docu.module#DocuPageModule' },
  { path: 'profile', loadChildren: './profile/profile/profile.module#ProfilePageModule' },
  { path: 'manager-dashboard', loadChildren: './manager/dashboard/dashboard.module#DashboardPageModule' },
  { path: 'directory', loadChildren: './manager/directory/directory.module#DirectoryPageModule' },
  { path: 'manager-leaves', loadChildren: './manager/manager-leaves/manager-leaves.module#ManagerLeavesPageModule' },
  { path: 'manager-timesheets', loadChildren: './manager/manager-timesheets/manager-timesheets.module#ManagerTimesheetsPageModule' },
  { path: 'manager-undertime', loadChildren: './manager/manager-undertime/manager-undertime.module#ManagerUndertimePageModule' },
  { path: 'manager-tardiness', loadChildren: './manager/manager-tardiness/manager-tardiness.module#ManagerTardinessPageModule' },
  { path: 'manager-overtime', loadChildren: './manager/manager-overtime/manager-overtime.module#ManagerOvertimePageModule' },
  { path: 'manager-shifts', loadChildren: './manager/manager-shifts/manager-shifts.module#ManagerShiftsPageModule' },
  { path: 'todo-timesheets', loadChildren: './manager/todo-timesheets/todo-timesheets.module#TodoTimesheetsPageModule' },
  { path: 'todo-restday', loadChildren: './manager/todo-restday/todo-restday.module#TodoRestdayPageModule' },
  { path: 'todo-holiday', loadChildren: './manager/todo-holiday/todo-holiday.module#TodoHolidayPageModule' },
  { path: 'todo-overtime', loadChildren: './manager/todo-overtime/todo-overtime.module#TodoOvertimePageModule' },
  { path: 'todo-leaves', loadChildren: './manager/todo-leaves/todo-leaves.module#TodoLeavesPageModule' },
  { path: 'todo-shifts', loadChildren: './manager/todo-shifts/todo-shifts.module#TodoShiftsPageModule' },
  { path: 'todo-mobile', loadChildren: './manager/todo-mobile/todo-mobile.module#TodoMobilePageModule' },
  { path: 'timesheet-details', loadChildren: './hours/timesheet-details/timesheet-details.module#TimesheetDetailsPageModule' },
  { path: 'overtime-details', loadChildren: './hours/overtime-details/overtime-details.module#OvertimeDetailsPageModule' },
  { path: 'leave-details', loadChildren: './leaves/leave-details/leave-details.module#LeaveDetailsPageModule' },
  { path: 'personal', loadChildren: './profile/personal/personal.module#PersonalPageModule' },
  { path: 'employment', loadChildren: './profile/employment/employment.module#EmploymentPageModule' },
  { path: 'compensation', loadChildren: './profile/compensation/compensation.module#CompensationPageModule' },
  { path: 'compensation-history', loadChildren: './profile/compensation-history/compensation-history.module#CompensationHistoryPageModule' },
  { path: 'miss-punch', loadChildren: './dash/miss-punch/miss-punch.module#MissPunchPageModule' },
  { path: 'directory-details', loadChildren: './manager/directory-details/directory-details.module#DirectoryDetailsPageModule' },
  { path: 'add-timesheet', loadChildren: './hours/add-timesheet/add-timesheet.module#AddTimesheetPageModule' },
  { path: 'change-logs', loadChildren: './hours/change-logs/change-logs.module#ChangeLogsPageModule' },
  { path: 'apply-ot', loadChildren: './hours/apply-ot/apply-ot.module#ApplyOtPageModule' },
  { path: 'apply', loadChildren: './leaves/apply/apply.module#ApplyPageModule' },
  { path: 'employee-qr', loadChildren: './employee-qr/employee-qr.module#EmployeeQrPageModule' },
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  exports: [RouterModule],
  declarations: [MemberRoutingPage]
})
export class MemberRoutingPageModule {}
