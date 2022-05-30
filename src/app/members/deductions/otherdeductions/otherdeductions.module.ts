import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Routes, RouterModule } from '@angular/router';

import { IonicModule } from '@ionic/angular';

import { OtherdeductionsPage } from './otherdeductions.page';

const routes: Routes = [
  {
    path: '',
    component: OtherdeductionsPage
  }
];

@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    IonicModule,
    RouterModule.forChild(routes)
  ],
  declarations: [OtherdeductionsPage]
})
export class OtherdeductionsPageModule {}
