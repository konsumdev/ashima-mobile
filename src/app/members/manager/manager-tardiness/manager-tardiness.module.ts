import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { ManagerTardinessPage } from './manager-tardiness.page';

const routes: Routes = [
  {
    path: '',
    component: ManagerTardinessPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [ManagerTardinessPage]
})
export class ManagerTardinessPageModule {}
