import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerTimesheetsPage } from './manager-timesheets.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerTimesheetsPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerTimesheetsPage]
})
export class ManagerTimesheetsPageModule {}
