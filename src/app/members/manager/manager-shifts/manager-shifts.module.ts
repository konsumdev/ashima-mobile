import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerShiftsPage } from './manager-shifts.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerShiftsPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerShiftsPage]
})
export class ManagerShiftsPageModule {}
