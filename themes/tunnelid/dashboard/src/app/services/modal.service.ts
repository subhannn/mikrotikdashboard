import { Injectable, OnInit, Component, Inject } from '@angular/core';
import {MatDialog, MatDialogRef, MAT_DIALOG_DATA} from '@angular/material';

export interface DialogData {
  title: string;
  message: string;
}

@Injectable()
export class ModalService {

  constructor(
  	private dialog: MatDialog
  ) { }

  confirmation(message: string){
  	const dialogRef = this.dialog.open(ConfirmationModalComponent, {
  		width: '350px',
  		disableClose: true,
  		data: { message: message }
  	})

  	return dialogRef.afterClosed()
  }
}

@Component({
  selector: 'app-modal-confirmation',
  templateUrl: './modal/confirm.modal.component.html'
})
export class ConfirmationModalComponent{
  constructor(
  	public dialogRef: MatDialogRef<ConfirmationModalComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DialogData
  ) { }

  onCancel(){
  	this.dialogRef.close()
  }
}
