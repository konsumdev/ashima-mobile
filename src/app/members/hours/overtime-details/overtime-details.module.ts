import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { OvertimeDetailsPage } from './overtime-details.page';

const routes: Routes = [
  {
    path: '',
    component: OvertimeDetailsPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [OvertimeDetailsPage]
})
export class OvertimeDetailsPageModule {}
