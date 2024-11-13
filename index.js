async function enviarFormulario(event) {
    event.preventDefault();
    const form = document.getElementById('consultaForm');
    const formData = new FormData(form);

    const loadingOverlay = document.getElementById("loadingOverlay");
    loadingOverlay.classList.add("active");

    // Inicializa a contagem e a lista dos boletos encontrados
    let encontrados = 0;
    let pdf_link = [];

    try {
        const response = await fetch('server/index.php', {
            method: 'POST',
            body: formData,
        });

        if (response.status !== 200) {
            loadingOverlay.classList.remove("active");
            const result = await response.json();
            mostrarPopup("warning", result.error || "Ocorreu um erro ao processar o formulário.");
            return;
        }

        const results = await response.json();
        loadingOverlay.classList.remove("active");

        // Verifica se há resultados
        if (Object.keys(results).length > 0) {
            Object.entries(results).forEach(([empresa, pdfBase64]) => {
                if (pdfBase64) {
                    // Converte o Base64 para Blob
                    const byteCharacters = atob(pdfBase64);
                    const byteNumbers = new Array(byteCharacters.length);
                    for (let i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    const byteArray = new Uint8Array(byteNumbers);
                    const pdfBlob = new Blob([byteArray], { type: 'application/pdf' });

                    // Cria uma URL para o Blob e abre em uma nova aba
                    const pdfUrl = URL.createObjectURL(pdfBlob);
                    
                    const link = document.createElement('a');
                    link.href = pdfUrl;
                    link.target = '_blank';
                    link.download = `boletos_${empresa}.pdf`;
                    link.click();

                    // Libera a URL do Blob após abrir a aba
                    setTimeout(() => URL.revokeObjectURL(pdfUrl), 100);

                    // Armazena a URL dos PDFs encontrados
                    if (pdfUrl !== 'null') {
                        encontrados++;
                        pdf_link.push(pdfUrl);
                    }
                } else {
                    // Você pode adicionar uma mensagem específica para cada empresa aqui, se necessário.
                    console.log(`Nenhum boleto encontrado para ${empresa}.`);
                }
            });
        }

        // Mostra o popup apropriado dependendo do número de boletos encontrados
        if (encontrados === 0) {
            mostrarPopup("warning", "Nenhum boleto foi encontrado. Para mais informações, entre em contato com o time de pos vendas no (31)4007-2565");
        } else {
            mostrarPopup("success", "Boleto(s) encontrado(s). Verifique seus downloads.");
        }  

    } catch (error) {
        loadingOverlay.classList.remove("active");
        mostrarPopup("error", "Erro ao enviar a requisição");
    }
}


function aplicarMascaraCPFCNPJ(input) {
    let valor = input.value.replace(/\D/g, "");
    if (valor.length <= 11) {
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
        valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    } else {
        valor = valor.replace(/^(\d{2})(\d)/, "$1.$2");
        valor = valor.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
        valor = valor.replace(/\.(\d{3})(\d)/, ".$1/$2");
        valor = valor.replace(/(\d{4})(\d)/, "$1-$2");
    }
    input.value = valor.substring(0, 18);
}

function validarEmail(email) {
    const regex = /^[\w.-]+@[a-zA-Z\d.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}

function validarCPFCNPJ(input) {
    const valor = input.value.replace(/\D/g, "");
    if (valor.length === 11 || valor.length === 14) {
        return true;
    } else {
        mostrarErro(input, 'CPF ou CNPJ inválido.');
        return false;
    }
}

function mostrarErro(input, mensagem) {
    const errorElement = input.nextElementSibling;
    
    //errorElement.innerText = mensagem;
}

function limparErro(input) {
    const errorElement = input.nextElementSibling;
    //errorElement.innerText = "";
}

async function buscarCEP() {
    const cep = document.getElementById("cep").value;
    if (/^\d{5}-?\d{3}$/.test(cep)) {
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
            const data = await response.json();
            if (!data.erro) {
                mostrarPopup("success", `Endereço: ${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}`);
            } else {
                mostrarPopup("warning", "Não foi possível encontrar o endereço para o CEP fornecido.");
            }
        } catch (error) {
            mostrarPopup("warning", "Não foi possível buscar o CEP.");
        }
    } else {
        mostrarPopup("warning", "CEP inválido.");
    }
}

function mostrarPopup(tipo, mensagem) {
    const overlay = document.getElementById("overlay");
    const popup = document.getElementById("popup");
    popup.className = `popup ${tipo}`;
    popup.querySelector("h3").innerText = tipo === "success" ? "Sucesso!" : tipo === "error" ? "Erro!" : "Atenção";
    popup.querySelector("p").innerText = mensagem;
    overlay.classList.add("active");
}


function fecharPopup() {
    document.getElementById("overlay").classList.remove("active");
}

function validarFormulario(event) {
    let isValid = true;
    
    const emailInput = document.getElementById("email");
    if (!validarEmail(emailInput.value)) {
        mostrarErro(emailInput, "E-mail inválido.");
        isValid = false;
    } else {
        limparErro(emailInput);
    }

    const cpfCnpjInput = document.getElementById("cnpj");
    if (!validarCPFCNPJ(cpfCnpjInput)) {
        isValid = false;
    } else {
        limparErro(cpfCnpjInput);
    }

    const anoInput = document.getElementById("ano");
    const currentYear = new Date().getFullYear();
    if (anoInput.value < 2000 || anoInput.value > currentYear) {
        mostrarErro(anoInput, "Ano inválido.");
        isValid = false;
    } else {
        limparErro(anoInput);
    }

    if (!isValid) {
        event.preventDefault();
    }
}



